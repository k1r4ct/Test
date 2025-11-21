import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';

@Component({
  selector: 'app-attachment-preview-modal',
  templateUrl: './attachment-preview-modal.component.html',
  styleUrls: ['./attachment-preview-modal.component.scss'],
  standalone: false
})
export class AttachmentPreviewModalComponent implements OnInit {
  attachment: any;
  isPending: boolean = false;
  previewUrl: SafeResourceUrl | string | null = null;
  isImage: boolean = false;
  isPdf: boolean = false;
  isPreviewable: boolean = false;
  fileExtension: string = '';
  downloadUrl: string = '';

  constructor(
    public dialogRef: MatDialogRef<AttachmentPreviewModalComponent>,
    @Inject(MAT_DIALOG_DATA) public data: any,
    private sanitizer: DomSanitizer
  ) {
    this.attachment = data.attachment;
    this.isPending = data.isPending || false;
  }

  ngOnInit(): void {
    this.initializePreview();
  }

  /**
   * Initialize preview based on file type
   */
  private initializePreview(): void {
    if (this.isPending) {
      // Pending file - use local preview
      this.initializePendingPreview();
    } else {
      // Existing attachment - use server URL
      this.initializeExistingPreview();
    }
  }

  /**
   * Initialize preview for pending (not yet uploaded) file
   */
  private initializePendingPreview(): void {
    const file = this.attachment.file;
    
    if (!file) {
      console.error('No file provided for pending attachment');
      return;
    }

    this.fileExtension = this.getFileExtension(file.name);
    
    // Check if it's an image
    if (file.type.startsWith('image/')) {
      this.isImage = true;
      this.isPreviewable = true;
      
      // Use existing preview or create new one
      if (this.attachment.preview) {
        this.previewUrl = this.attachment.preview;
      } else {
        const reader = new FileReader();
        reader.onload = (e: any) => {
          this.previewUrl = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    }
    // Check if it's a PDF
    else if (file.type === 'application/pdf') {
      this.isPdf = true;
      this.isPreviewable = true;
      
      // Create blob URL for PDF
      const blob = new Blob([file], { type: 'application/pdf' });
      const blobUrl = URL.createObjectURL(blob);
      this.previewUrl = this.sanitizer.bypassSecurityTrustResourceUrl(blobUrl);
    }
    // Other file types - not previewable
    else {
      this.isPreviewable = false;
    }
  }

  /**
   * Initialize preview for existing attachment
   */
  private initializeExistingPreview(): void {
    const fileName = this.attachment.original_name || this.attachment.file_name || '';
    this.fileExtension = this.getFileExtension(fileName);
    
    // Set download URL
    this.downloadUrl = `/api/attachments/${this.attachment.id}/download`;
    
    // Determine file type from mime_type or extension
    const mimeType = this.attachment.mime_type || '';
    
    // Check if it's an image
    if (mimeType.startsWith('image/') || this.isImageExtension(this.fileExtension)) {
      this.isImage = true;
      this.isPreviewable = true;
      this.previewUrl = this.downloadUrl;
    }
    // Check if it's a PDF
    else if (mimeType === 'application/pdf' || this.fileExtension === 'pdf') {
      this.isPdf = true;
      this.isPreviewable = true;
      this.previewUrl = this.sanitizer.bypassSecurityTrustResourceUrl(this.downloadUrl);
    }
    // Other file types - not previewable
    else {
      this.isPreviewable = false;
    }
  }

  /**
   * Get file extension from filename
   */
  private getFileExtension(filename: string): string {
    if (!filename) return '';
    return filename.split('.').pop()?.toLowerCase() || '';
  }

  /**
   * Check if extension is an image
   */
  private isImageExtension(ext: string): boolean {
    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
    return imageExtensions.includes(ext);
  }

  /**
   * Download file
   */
  download(): void {
    if (this.isPending) {
      // For pending files, trigger browser download
      const file = this.attachment.file;
      const url = URL.createObjectURL(file);
      const a = document.createElement('a');
      a.href = url;
      a.download = file.name;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    } else {
      // For existing attachments, open download URL
      window.open(this.downloadUrl, '_blank');
    }
  }

  /**
   * Close modal
   */
  close(): void {
    this.dialogRef.close();
  }

  /**
   * Get file icon based on extension
   */
  getFileIcon(): string {
    const iconMap: {[key: string]: string} = {
      // Images
      'jpg': 'image',
      'jpeg': 'image',
      'png': 'image',
      'gif': 'image',
      'webp': 'image',
      'svg': 'image',
      
      // Documents
      'pdf': 'picture_as_pdf',
      'doc': 'description',
      'docx': 'description',
      'txt': 'description',
      
      // Spreadsheets
      'xls': 'table_chart',
      'xlsx': 'table_chart',
      'csv': 'table_chart',
      
      // Presentations
      'ppt': 'slideshow',
      'pptx': 'slideshow',
      
      // Archives
      'zip': 'folder_zip',
      'rar': 'folder_zip',
      '7z': 'folder_zip'
    };
    
    return iconMap[this.fileExtension] || 'insert_drive_file';
  }

  /**
   * Format file size
   */
  formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 B';
    
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }
}
