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
