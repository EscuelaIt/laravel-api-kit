import { ResponseApiAdapter } from '@dile/crud/lib/ResponseApiAdapter';

export class MyResponseApiAdapter extends ResponseApiAdapter {
  getElementList() {
    return this.response.data.result.data;
  }
}