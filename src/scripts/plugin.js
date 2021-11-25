/*
  Project: RFS ACF JSON Sync Notice
  Author: rfswp
 */

/* global RFS_ACF_SYNC_NOTICE */

class RfsAcfJsonSyncNotice {
  constructor() {
    this.data = RFS_ACF_SYNC_NOTICE;
  }

  init() {
    switch (this.data.mode) {
      case 'notice':
        this.noticeMode();
        break;
      default:
        this.autoMode();
        break;
    }
  }

  autoMode() {
    if (!this.data.doAutoSync) {
      return;
    }

    const container = document.createElement('div');
    const inner = document.createElement('div');
    container.classList.add(...['rfs-acf-sync-auto-container', 'show']);
    inner.classList.add('rfs-acf-sync-auto-inner');
    const spinner = document.createElement('span');
    spinner.classList.add(...['spinner', 'is-active']);
    const message = document.createElement('p');

    Object.assign(message, {
      className: 'rfs-acf-sync-auto-message',
      innerText: this.data.autoSync.syncing,
    });

    inner.appendChild(spinner);
    inner.appendChild(message);
    container.appendChild(inner);
    document.body.appendChild(container);

    fetch(this.data.ajaxurl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Cache-Control': 'no-cache',
      },
      body: new URLSearchParams({
        action: 'rfs_acf_auto_sync',
        security: this.data.nonce,
        sync_data: JSON.stringify(this.data.syncData),
        files: JSON.stringify(this.data.files),
        url: this.data.acfPageUrl,
        auto_sync_mode: this.data.autoSyncMode,
      }),
    })
      .then(response => response.json())
      .then(response => {
        if (response.ok) {
          if (response.redirectUrl === 'none') {
            spinner.remove();
            message.innerText = this.data.autoSync.synced;
            message.classList.add('is-synced');

            setTimeout(() => {
              container.classList.remove('show');
            }, 500);
          } else {
            setTimeout(() => {
              window.location.replace(response.redirectUrl);
            }, 1000);
          }
        }
      })
      .catch(error => {
        console.log(error);
      });
  }

  noticeMode() {
    if (!this.data.groupHasSync) {
      return;
    }

    const modal = document.createElement('div');
    modal.classList.add('rfs-acf-sync-modal');
    const modalInner = document.createElement('div');
    modalInner.classList.add('rfs-acf-sync-modal-inner');
    const modalHeading = document.createElement('h2');
    const modalText = document.createElement('p');
    const modalButtonsContainer = document.createElement('div');
    modalButtonsContainer.classList.add('rfs-acf-sync-modal-buttons');
    const modalButtonSync = document.createElement('a');
    const modalButtonIgnore = document.createElement('a');

    Object.assign(modalHeading, {
      className: 'rfs-acf-sync-modal-heading',
      innerText: this.data.modal.heading,
    });

    Object.assign(modalText, {
      className: 'rfs-acf-sync-modal-text',
      innerText: this.data.modal.text,
    });

    Object.assign(modalButtonSync, {
      href: this.data.modal.buttons.sync.url,
      className: 'rfs-acf-modal-button button button-primary button-large',
      innerText: this.data.modal.buttons.sync.label,
    });

    Object.assign(modalButtonIgnore, {
      href: this.data.modal.buttons.ignore.url,
      className: 'rfs-acf-modal-button button page-title-action',
      role: 'button',
      innerText: this.data.modal.buttons.ignore.label,
    });

    modalButtonsContainer.appendChild(modalButtonSync);
    modalButtonsContainer.appendChild(modalButtonIgnore);
    modalInner.appendChild(modalHeading);
    modalInner.appendChild(modalText);
    modalInner.appendChild(modalButtonsContainer);
    modal.appendChild(modalInner);
    document.body.appendChild(modal);

    modalButtonIgnore.addEventListener('click', e => {
      e.preventDefault();
      modal.classList.add('hide');
    });
  }
}

const plugin = new RfsAcfJsonSyncNotice();
plugin.init();
