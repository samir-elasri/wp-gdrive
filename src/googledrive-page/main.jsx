import { createRoot, render, createElement, Fragment, StrictMode, useState, useEffect, createInterpolateElement } from '@wordpress/element';
import { Button, TextControl, Spinner, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import "./scss/style.scss"

const _config = window.WPMUDEV_PLUGINTEST || {};
const domElement = document.getElementById(_config.dom_element_id || 'wpmudev_plugintest_drive_main_wrap');

const api = (path, { method = 'GET', body, nonce } = {}) => {
  const headers = { 'Content-Type': 'application/json' };
  if (nonce) headers['X-WP-Nonce'] = nonce;
  return fetch(path, { method, headers, body: body ? JSON.stringify(body) : undefined })
    .then(async (res) => {
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(data?.message || res.statusText);
      return data;
    });
};

const WPMUDEV_DriveTest = () => {
    const [isAuthenticated, setIsAuthenticated] = useState(window.WPMUDEV_PLUGINTEST.authStatus || false);
    const [hasCredentials, setHasCredentials] = useState(window.WPMUDEV_PLUGINTEST.hasCredentials || false);
    const [showCredentials, setShowCredentials] = useState(!window.WPMUDEV_PLUGINTEST.hasCredentials);
    const [isLoading, setIsLoading] = useState(false);
    const [files, setFiles] = useState([]);
    const [uploadFile, setUploadFile] = useState(null);
    const [folderName, setFolderName] = useState('');
    const [notice, setNotice] = useState({ message: '', type: '' });
    const [credentials, setCredentials] = useState({
        clientId: '',
        clientSecret: ''
    });

    useEffect(() => {
      const onMsg = (e) => {
        if (e?.data?.type === 'wpmudev-drive-auth' && e.data.success) {
          onAuthorized && onAuthorized();
        }
      };
      window.addEventListener('message', onMsg);
      return () => window.removeEventListener('message', onMsg);
    }, [onAuthorized]);

    const showNotice = (message, type = 'success') => {
        setNotice({ message, type });
        setTimeout(() => setNotice({ message: '', type: '' }), 5000);
    };

    const handleSaveCredentials = async () => {
    };

    const handleAuth = async () => {
    };

    const loadFiles = async () => {

    };

    const handleUpload = async () => {
    };

    const handleDownload = async (fileId, fileName) => {
    };

    const handleCreateFolder = async () => {
    };

    return (
        <>
            <div className="sui-header">
                <h1 className="sui-header-title">
                    Google Drive Test
                </h1>
                <p className="sui-description">Test Google Drive API integration for applicant assessment</p>
            </div>

            {notice.message && (
                <Notice status={notice.type} isDismissible onRemove=''>
                    {notice.message}
                </Notice>
            )}

            {showCredentials ? (
                <div className="sui-box">
                    <div className="sui-box-header">
                        <h2 className="sui-box-title">Set Google Drive Credentials</h2>
                    </div>
                    <div className="sui-box-body">
                        <div className="sui-box-settings-row">
                            <TextControl
                                help={createInterpolateElement(
                                    'You can get Client ID from <a>Google Cloud Console</a>. Make sure to enable Google Drive API.',
                                    {
                                        a: <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener noreferrer" />,
                                    }
                                )}
                                label="Client ID"
                                value={credentials.clientId}
                                onChange={(value) => setCredentials({...credentials, clientId: value})}
                            />
                        </div>

                        <div className="sui-box-settings-row">
                            <TextControl
                                help={createInterpolateElement(
                                    'You can get Client Secret from <a>Google Cloud Console</a>.',
                                    {
                                        a: <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener noreferrer" />,
                                    }
                                )}
                                label="Client Secret"
                                value={credentials.clientSecret}
                                onChange={(value) => setCredentials({...credentials, clientSecret: value})}
                                type="password"
                            />
                        </div>

                        <div className="sui-box-settings-row">
                            <span>Please use this URL <em>{window.WPMUDEV_PLUGINTEST.redirectUri}</em> in your Google API's <strong>Authorized redirect URIs</strong> field.</span>
                        </div>

                        <div className="sui-box-settings-row">
                            <p><strong>Required scopes for Google Drive API:</strong></p>
                            <ul>
                                <li>https://www.googleapis.com/auth/drive.file</li>
                                <li>https://www.googleapis.com/auth/drive.readonly</li>
                            </ul>
                        </div>
                    </div>
                    <div className="sui-box-footer">
                        <div className="sui-actions-right">
                            <Button
                                variant="primary"
                                onClick={handleSaveCredentials}
                                disabled={isLoading}
                            >
                                {isLoading ? <Spinner /> : 'Save Credentials'}
                            </Button>
                        </div>
                    </div>
                </div>
            ) : !isAuthenticated ? (
                <div className="sui-box">
                    <div className="sui-box-header">
                        <h2 className="sui-box-title">Authenticate with Google Drive</h2>
                    </div>
                    <div className="sui-box-body">
                        <div className="sui-box-settings-row">
                            <p>Please authenticate with Google Drive to proceed with the test.</p>
                            <p><strong>This test will require the following permissions:</strong></p>
                            <ul>
                                <li>View and manage Google Drive files</li>
                                <li>Upload new files to Drive</li>
                                <li>Create folders in Drive</li>
                            </ul>
                        </div>
                    </div>
                    <div className="sui-box-footer">
                        <div className="sui-actions-left">
                            <Button
                                variant="secondary"
                                onClick={() => setShowCredentials(true)}
                            >
                                Change Credentials
                            </Button>
                        </div>
                        <div className="sui-actions-right">
                            <Button
                                variant="primary"
                                onClick={handleAuth}
                                disabled={isLoading}
                            >
                                {isLoading ? <Spinner /> : 'Authenticate with Google Drive'}
                            </Button>
                        </div>
                    </div>
                </div>
            ) : (
                <>
                    {/* File Upload Section */}
                    <div className="sui-box">
                        <div className="sui-box-header">
                            <h2 className="sui-box-title">Upload File to Drive</h2>
                        </div>
                        <div className="sui-box-body">
                            <div className="sui-box-settings-row">
                                <input
                                    type="file"
                                    onChange={(e) => setUploadFile(e.target.files[0])}
                                    className="drive-file-input"
                                />
                                {uploadFile && (
                                    <p><strong>Selected:</strong> {uploadFile.name} ({Math.round(uploadFile.size / 1024)} KB)</p>
                                )}
                            </div>
                        </div>
                        <div className="sui-box-footer">
                            <div className="sui-actions-right">
                                <Button
                                    variant="primary"
                                    onClick={handleUpload}
                                    disabled={isLoading || !uploadFile}
                                >
                                    {isLoading ? <Spinner /> : 'Upload to Drive'}
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Create Folder Section */}
                    <div className="sui-box">
                        <div className="sui-box-header">
                            <h2 className="sui-box-title">Create New Folder</h2>
                        </div>
                        <div className="sui-box-body">
                            <div className="sui-box-settings-row">
                                <TextControl
                                    label="Folder Name"
                                    value={folderName}
                                    onChange={setFolderName}
                                    placeholder="Enter folder name"
                                />
                            </div>
                        </div>
                        <div className="sui-box-footer">
                            <div className="sui-actions-right">
                                <Button
                                    variant="secondary"
                                    onClick={handleCreateFolder}
                                    disabled={isLoading || !folderName.trim()}
                                >
                                    {isLoading ? <Spinner /> : 'Create Folder'}
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Files List Section */}
                    <div className="sui-box">
                        <div className="sui-box-header">
                            <h2 className="sui-box-title">Your Drive Files</h2>
                            <div className="sui-actions-right">
                                <Button
                                    variant="secondary"
                                    onClick={loadFiles}
                                    disabled={isLoading}
                                >
                                    {isLoading ? <Spinner /> : 'Refresh Files'}
                                </Button>
                            </div>
                        </div>
                        <div className="sui-box-body">
                            {isLoading ? (
                                <div className="drive-loading">
                                    <Spinner />
                                    <p>Loading files...</p>
                                </div>
                            ) : files.length > 0 ? (
                                <div className="drive-files-grid">
                                    {files.map((file) => (
                                        <div key={file.id} className="drive-file-item">
                                            <div className="file-info">
                                                <strong>{file.name}</strong>
                                                <small>
                                                    {file.modifiedTime ? new Date(file.modifiedTime).toLocaleDateString() : 'Unknown date'}
                                                </small>
                                            </div>
                                            <div className="file-actions">
                                                {file.webViewLink && (
                                                    <Button
                                                        variant="link"
                                                        size="small"
                                                        href=''
                                                        target="_blank"
                                                    >
                                                        View in Drive
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="sui-box-settings-row">
                                    <p>No files found in your Drive. Upload a file or create a folder to get started.</p>
                                </div>
                            )}
                        </div>
                    </div>
                </>
            )}
        </>
    );
}

// [ADD] CredentialsForm
function CredentialsForm({ nonce, endpoints, onSaved, hasCredentials, redirectUri }) {
  const [clientId, setClientId] = useState('');
  const [clientSecret, setClientSecret] = useState('');

  const save = async () => {
    await api(endpoints.restEndpointSave, {
      method: 'POST',
      body: { client_id: clientId.trim(), client_secret: clientSecret.trim() },
      nonce,
    });
    onSaved && onSaved();
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
            <code>https://www.googleapis.com/auth/drive.file</code>&nbsp;
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
      if (!data?.authUrl) throw new Error(__('Auth URL not received', 'wpmudev-plugin-test'));

      const w = 600, h = 700;
      const y = window.top.outerHeight / 2 + window.top.screenY - (h / 2);
      const x = window.top.outerWidth / 2 + window.top.screenX - (w / 2);
      const popup = window.open(
        data.authUrl,
        'wpmudev-google-auth',
        `width=${w},height=${h},left=${x},top=${y},resizable,scrollbars`
      );

      if (!popup || popup.closed || typeof popup.closed === 'undefined') {
        window.location.assign(data.authUrl);
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

// [ADD] FilesBlock
function FilesBlock({ nonce, endpoints }) {
  const [query, setQuery] = useState('trashed=false');
  const [pageSize, setPageSize] = useState(20);
  const [files, setFiles] = useState([]);
  const [uploadFile, setUploadFile] = useState(null);
  const [newFolder, setNewFolder] = useState('');
  const [uploading, setUploading] = useState(false);
  const fileInputRef = useRef(null);
  const [inputKey, setInputKey] = useState(0);

  const load = async () => {
    const data = await api(endpoints.restEndpointFiles + `?q=${encodeURIComponent(query)}&page_size=${pageSize}`, { nonce });
    setFiles(data?.files || []);
  };

  const upload = async () => {
    if (!uploadFile || uploading) return;
    setUploading(true);
    try {
      const res = await fetch(endpoints.restEndpointUpload, {
        method: 'POST',
        headers: { 'X-WP-Nonce': nonce },
        body: (() => { const f = new FormData(); f.append('file', uploadFile); return f; })(),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data?.message || 'Upload failed');

      setUploadFile(null);
      if (fileInputRef.current) fileInputRef.current.value = '';
      setInputKey(k => k + 1);

      alert(__('Uploaded', 'wpmudev-plugin-test'));
      load();
    } catch (e) {
      alert(e.message);
    } finally {
      setUploading(false);
    }
  };

  const download = async (id) => {
    const data = await api(endpoints.restEndpointDownload + `?file_id=${encodeURIComponent(id)}`, { nonce });
    const b64 = data.content || '';
    const binary = atob(b64);
    const bytes = new Uint8Array([...binary].map(c => c.charCodeAt(0)));
    const blob = new Blob([bytes], { type: data.mimeType || 'application/octet-stream' });
    const a = document.createElement('a'); a.href = URL.createObjectURL(blob);
    a.download = data.filename || 'download'; a.click(); URL.revokeObjectURL(a.href);
  };

  const createFolder = async () => {
    if (!newFolder.trim()) return;
    await api(endpoints.restEndpointCreate, { method: 'POST', body: { name: newFolder.trim() }, nonce });
    setNewFolder(''); load();
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
            <input
              key={inputKey}
              ref={fileInputRef}
              type="file"
              onChange={e => setUploadFile(e.target.files?.[0] || null)}
            />
          </div>
          <div className="sui-col-md-3">
            <label className="sui-label">&nbsp;</label>
            <button
              className="sui-button"
              onClick={upload}
              disabled={!uploadFile || uploading}
            >
              {uploading ? __('Uploading…', 'wpmudev-plugin-test') : __('Upload', 'wpmudev-plugin-test')}
            </button>
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

// [ADD/MERGE] App
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

// if ( createRoot ) {
//     createRoot( domElement ).render(<StrictMode><WPMUDEV_DriveTest/></StrictMode>);
// } else {
//     render( <StrictMode><WPMUDEV_DriveTest/></StrictMode>, domElement );
// }

// [ADD bootstrapping]
document.addEventListener('DOMContentLoaded', () => {
  const localized = window.WPMUDEV_PLUGINTEST || {};
  const root = document.getElementById(localized.dom_element_id || 'wpmudev_plugintest_drive_main_wrap');
  if (root) render(<App />, root);
});
