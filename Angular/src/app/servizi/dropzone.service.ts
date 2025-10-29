/* import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class DropzoneService {
  private dropzoneInstance: Dropzone | null = null;

  setDropzoneInstance(instance: Dropzone) {
    this.dropzoneInstance = instance;
    //console.log(this.dropzoneInstance);
    
  }

  destroyDropzone() {
    //console.log("distruggo dropzone");
    
    this.dropzoneInstance?.destroy();
    if (this.dropzoneInstance) {
      this.dropzoneInstance = null;
    }
  }
}
 */