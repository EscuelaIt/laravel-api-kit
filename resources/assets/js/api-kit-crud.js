import { LitElement, html, css } from 'lit';
import '@dile/crud/components/crud/crud.js';
import { CrudConfigBuilder } from '@dile/crud/lib/CrudConfigBuilder';
import { MyResponseApiAdapter } from './responseApiAdapter.js';


export class ApiKitCrud extends LitElement {
  static styles = [
    css`
      :host {
        display: block;
      }
    `
  ];

  static get properties() {
    return {
      endpoint: { type: String },
      config: { type: Object },
      generatedConfig: { type: Object },
    };
  }

  firstUpdated() {
    this.config = {
      ...this.config,
      responseAdapter: new MyResponseApiAdapter(),
    }
    this.generatedConfig = new CrudConfigBuilder(this.endpoint, this.config).getConfig();
    this.generatedConfig.templates.item = this.getItemTemplate();
  }

  render() {
    return html`
      ${this.generatedConfig 
        ? html`
            <dile-crud
              .config="${this.generatedConfig}">
            </dile-crud>
          `
        : html`<p>Loading...</p>`}
    `;
  }

  getItemTemplate() {

  }
}
customElements.define('api-kit-crud', ApiKitCrud);