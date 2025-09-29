import { render, createElement, Fragment, useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const api = (path, { method = 'GET', body, nonce } = {}) => {
  const headers = { 'Content-Type': 'application/json' };
  if (nonce) headers['X-WP-Nonce'] = nonce;
  return fetch(path, { method, headers, body: body ? JSON.stringify(body) : undefined })
    .then(async (res) => {
      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        const msg = data?.message || res.statusText;
        throw new Error(msg);
      }
      return data;
    });
};

function CredentialsForm({ nonce, endpoints, onSaved, hasCredentials, redirectUri }) {
  const [clientId, setClientId] = useState('');
  const [clientSecret, setClientSecret] = useState('');

  const save = async () => {
    await api(endpoints.restEndpointSave, {
      method: 'POST',
      body: { client_id: clientId.trim(), client_secret: clientSecret.trim() },
      nonce,
    });
    onSaved();
  };

  return (
    <div className="sui-box">
      <div className="sui-box-header">
        <h3 className="sui-box-title">{__('Google API Credentials', 'wpmudev-plugin-test')}</h3>
      </div>
      <div className="sui-box-body">
        <p>{__('Enter your Google OAuth 2.0 Client ID and Client Secret.', 'wpmudev-plugin-test')}</p>
        <div className="sui-form-field">
          <label className="sui-label">{__('Client ID', 'wpmudev-plugin-test')}</label>
          <input className="sui-form-control" value={clientId} onChange={e => setClientId(e.target.value)} />
        </div>
        <div className="sui-form-field">
          <label className="sui-label">{__('Client Secret', 'wpmudev-plugin-test')}</label>
          <input className="sui-form-control" value={clientSecret} onChange={e => setClientSecret(e.target.value)} />
        </div>

        <div className="sui-notice">
          <p><strong>{__('Redirect URI:', 'wpmudev-plugin-test')}</strong></p>
          <code>{redirectUri}</code>
          <p className="sui-description">
            {__('Copy this URI to your Google Cloud Console OAuth credential configuration.', 'wpmudev-plugin-test')}
          </p>
          <p className="sui-description">
            <strong>{__('Required Scopes', 'wpmudev-plugin-test')}:</strong><br/>
            <code>https://www.googleapis.com/auth/drive.file</code> &nbsp;
            <code>https://www.googleapis.com/auth/drive.metadata.readonly</code>
          </p>
        </div>
      </div>
      <div className="sui-box-footer">
        <button className="sui-button sui-button-blue" onClick={save}>
          { hasCredentials ? __('Update Credentials', 'wpmudev-plugin-test') : __('Save Credentials', 'wpmudev-plugin-test') }
        </button>
      </div>
    </div>
  );
}

function AuthBlock({ nonce, endpoints, authStatus, onAuthorized }) {
  const [loading, setLoading] = useState(false);
  const startAuth = async () => {
    setLoading(true);
    try {
      const data = await api(endpoints.restEndpointAuth, { method: 'POST', nonce });
      if (data?.authUrl) {
        window.location.assign(data.authUrl);
      } else {
        throw new Error(__('Auth URL not received', 'wpmudev-plugin-test'));
      }
    } catch (e) {
      alert(e.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="sui-box">
      <div className="sui-box-header">
        <h3 className="sui-box-title">{__('Authorization', 'wpmudev-plugin-test')}</h3>
      </div>
      <div className="sui-box-body">
        <p>
          {authStatus
            ? __('Your site is authorized with Google Drive.', 'wpmudev-plugin-test')
            : __('You are not authorized yet. Click the button below to connect Google Drive.', 'wpmudev-plugin-test')}
        </p>
      </div>
      <div className="sui-box-footer">
        {!authStatus && (
          <button className="sui-button sui-button-blue" onClick={startAuth} disabled={loading}>
            {loading ? __('Redirecting…', 'wpmudev-plugin-test') : __('Authorize with Google', 'wpmudev-plugin-test')}
          </button>
        )}
      </div>
    </div>
  );
}

function FilesBlock({ nonce, endpoints }) {
  const [query, setQuery] = useState('trashed=false');
  const [pageSize, setPageSize] = useState(20);
  const [files, setFiles] = useState([]);
  const [uploadFile, setUploadFile] = useState(null);
  const [newFolder, setNewFolder] = useState('');

  const load = async () => {
    const data = await api(endpoints.restEndpointFiles + `?q=${encodeURIComponent(query)}&page_size=${pageSize}`, { nonce });
    setFiles(data?.files || []);
  };

  const upload = async () => {
    if (!uploadFile) return;
    const res = await fetch(endpoints.restEndpointUpload, {
      method: 'POST',
      headers: { 'X-WP-Nonce': nonce },
      body: (() => {
        const form = new FormData();
        form.append('file', uploadFile);
        return form;
      })(),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data?.message || 'Upload failed');
    alert(__('Uploaded', 'wpmudev-plugin-test'));
    load();
  };

  const download = async (id) => {
    const data = await api(endpoints.restEndpointDownload + `?file_id=${encodeURIComponent(id)}`, { nonce });
    // decode base64 and trigger download
    const content = atob(data.content);
    const blob = new Blob([new Uint8Array([...content].map(c => c.charCodeAt(0)))], { type: data.mimeType || 'application/octet-stream' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = data.filename || 'download';
    a.click();
    URL.revokeObjectURL(a.href);
  };

  const createFolder = async () => {
    if (!newFolder.trim()) return;
    await api(endpoints.restEndpointCreate, { method: 'POST', body: { name: newFolder.trim() }, nonce });
    setNewFolder('');
    load();
  };

  return (
    <div className="sui-box">
      <div className="sui-box-header">
        <h3 className="sui-box-title">{__('Google Drive Files', 'wpmudev-plugin-test')}</h3>
      </div>
      <div className="sui-box-body">
        <div className="sui-row">
          <div className="sui-col-md-6">
            <label className="sui-label">{__('Query', 'wpmudev-plugin-test')}</label>
            <input className="sui-form-control" value={query} onChange={e => setQuery(e.target.value)} />
          </div>
          <div className="sui-col-md-3">
            <label className="sui-label">{__('Page size', 'wpmudev-plugin-test')}</label>
            <input type="number" min="1" max="100" className="sui-form-control" value={pageSize} onChange={e => setPageSize(+e.target.value || 20)} />
          </div>
          <div className="sui-col-md-3">
            <label className="sui-label">&nbsp;</label>
            <button className="sui-button sui-button-blue" onClick={load}>{__('List files', 'wpmudev-plugin-test')}</button>
          </div>
        </div>

        <hr />

        <div className="sui-row">
          <div className="sui-col-md-6">
            <label className="sui-label">{__('Upload file', 'wpmudev-plugin-test')}</label>
            <input type="file" onChange={e => setUploadFile(e.target.files?.[0] || null)} />
          </div>
          <div className="sui-col-md-3">
            <label className="sui-label">&nbsp;</label>
            <button className="sui-button" onClick={upload}>{__('Upload', 'wpmudev-plugin-test')}</button>
          </div>
        </div>

        <hr />

        <div className="sui-row">
          <div className="sui-col-md-6">
            <label className="sui-label">{__('New folder name', 'wpmudev-plugin-test')}</label>
            <input className="sui-form-control" value={newFolder} onChange={e => setNewFolder(e.target.value)} />
          </div>
          <div className="sui-col-md-3">
            <label className="sui-label">&nbsp;</label>
            <button className="sui-button" onClick={createFolder}>{__('Create folder', 'wpmudev-plugin-test')}</button>
          </div>
        </div>

        <hr />

        <ul className="sui-list">
          {files.map(f => (
            <li key={f.id} className="sui-list-item">
              <div className="sui-list-label">
                <strong>{f.name}</strong><br />
                <small>{f.mimeType} — {f.size || 0} — {f.modifiedTime}</small>
              </div>
              <div className="sui-list-actions">
                <a className="sui-button" href={f.webViewLink} target="_blank" rel="noreferrer">{__('Open', 'wpmudev-plugin-test')}</a>
                <button className="sui-button" onClick={() => download(f.id)}>{__('Download', 'wpmudev-plugin-test')}</button>
              </div>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
}

function App() {
  const localized = window.WPMUDEV_PLUGINTEST || {};
  const {
    dom_element_id: rootId,
    nonce,
    restEndpointSave,
    restEndpointAuth,
    restEndpointFiles,
    restEndpointUpload,
    restEndpointDownload,
    restEndpointCreate,
    authStatus,
    hasCredentials,
    redirectUri,
  } = localized;

  const endpoints = {
    restEndpointSave,
    restEndpointAuth,
    restEndpointFiles,
    restEndpointUpload,
    restEndpointDownload,
    restEndpointCreate,
  };

  const [stateHasCreds, setStateHasCreds] = useState(!!hasCredentials);
  const [stateAuth, setStateAuth] = useState(!!authStatus);

  // If we come back from Google's callback, server updated tokens -> reflect on refresh/interaction.
  useEffect(() => {
    // nothing required here; buttons will re-check via endpoints as needed
  }, []);

  return (
    <Fragment>
      <CredentialsForm
        nonce={nonce}
        endpoints={endpoints}
        hasCredentials={stateHasCreds}
        redirectUri={redirectUri}
        onSaved={() => setStateHasCreds(true)}
      />
      <AuthBlock
        nonce={nonce}
        endpoints={endpoints}
        authStatus={stateAuth}
        onAuthorized={() => setStateAuth(true)}
      />
      {stateAuth && <FilesBlock nonce={nonce} endpoints={endpoints} />}
    </Fragment>
  );
}

document.addEventListener('DOMContentLoaded', () => {
  const localized = window.WPMUDEV_PLUGINTEST || {};
  const root = document.getElementById(localized.dom_element_id || 'wpmudev_plugintest_drive_main_wrap');
  if (root) render(<App />, root);
});
