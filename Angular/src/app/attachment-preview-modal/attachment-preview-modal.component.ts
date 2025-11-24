import { Component, Inject, OnInit, OnDestroy } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { ApiService } from '../servizi/api.service';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-attachment-preview-modal',
  templateUrl: './attachment-preview-modal.component.html',
  styleUrls: ['./attachment-preview-modal.component.scss'],
  standalone: false
})
export class AttachmentPreviewModalComponent implements OnInit, OnDestroy {
  attachment: any;
  isPending: boolean = false;
  previewUrl: SafeResourceUrl | string | null = null;
  isImage: boolean = false;
  isPdf: boolean = false;
  isPreviewable: boolean = false;
  fileExtension: string = '';
  downloadUrl: string = '';
  isLoading: boolean = false;
  
  private subscriptions: Subscription[] = [];

  constructor(
    public dialogRef: MatDialogRef<AttachmentPreviewModalComponent>,
    @Inject(MAT_DIALOG_DATA) public data: any,
    private sanitizer: DomSanitizer,
    private apiService: ApiService  // ADDED: Inject ApiService
  ) {
    this.attachment = data.attachment;
    this.isPending = data.isPending || false;
  }

  ngOnInit(): void {
    this.initializePreview();
  }

  ngOnDestroy(): void {
    // Clean up subscriptions
    this.subscriptions.forEach(sub => sub.unsubscribe());
    
    // Revoke object URLs to prevent memory leaks
    if (this.previewUrl && typeof this.previewUrl === 'string' && this.previewUrl.startsWith('blob:')) {
      URL.revokeObjectURL(this.previewUrl);
    }
  }

  /**
   * Initialize preview based on file type
   */
  private initializePreview(): void {
    if (this.isPending) {
      // Pending file - use local preview
      this.initializePendingPreview();
    } else {
      // Existing attachment - fetch from server
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
   * Initialize preview for existing attachment - FIXED VERSION
   */
  private initializeExistingPreview(): void {
    const fileName = this.attachment.original_name || this.attachment.file_name || '';
    this.fileExtension = this.getFileExtension(fileName);
    
    // Determine file type from mime_type or extension
    const mimeType = this.attachment.mime_type || '';
    
    // Check if it's an image
    if (mimeType.startsWith('image/') || this.isImageExtension(this.fileExtension)) {
      this.isImage = true;
      this.isPreviewable = true;
      this.loadImagePreview();
    }
    // Check if it's a PDF
    else if (mimeType === 'application/pdf' || this.fileExtension === 'pdf') {
      this.isPdf = true;
      this.isPreviewable = true;
      this.loadPdfPreview();
    }
    // Other file types - not previewable
    else {
      this.isPreviewable = false;
    }
  }

  /**
   * Load image preview using ApiService - ADDED METHOD
   */
  private loadImagePreview(): void {
    this.isLoading = true;
    
    const downloadSub = this.apiService.downloadTicketAttachment(this.attachment.id).subscribe(
      (blob: Blob) => {
        // Create blob URL for image
        const blobUrl = URL.createObjectURL(blob);
        this.previewUrl = blobUrl;
        this.isLoading = false;
      },
      (error) => {
        console.error('Error loading image preview:', error);
        this.isLoading = false;
        this.isPreviewable = false;
      }
    );
    
    this.subscriptions.push(downloadSub);
  }

  /**
   * Load PDF preview using ApiService - ADDED METHOD
   */
  private loadPdfPreview(): void {
    this.isLoading = true;
    
    const downloadSub = this.apiService.downloadTicketAttachment(this.attachment.id).subscribe(
      (blob: Blob) => {
        // Create blob URL for PDF and bypass security
        const blobUrl = URL.createObjectURL(blob);
        this.previewUrl = this.sanitizer.bypassSecurityTrustResourceUrl(blobUrl);
        this.isLoading = false;
      },
      (error) => {
        console.error('Error loading PDF preview:', error);
        this.isLoading = false;
        this.isPreviewable = false;
      }
    );
    
    this.subscriptions.push(downloadSub);
  }

  /**
   * Get file extension from filename
   */
  private getFileExtension(filename: string): string {
    if (!filename) return '';
    return filename.split('.').pop()?.toLowerCase() || '';
  }

  /**
   * Check if extension is an image type
   */
  private isImageExtension(extension: string): boolean {
    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
    return imageExtensions.includes(extension.toLowerCase());
  }

  /**
   * Get appropriate icon for file type
   */
  getFileIcon(): string {
    if (this.isImage) return 'image';
    if (this.isPdf) return 'picture_as_pdf';
    
    const extension = this.fileExtension.toLowerCase();
    const iconMap: { [key: string]: string } = {
      'doc': 'description',
      'docx': 'description',
      'xls': 'table_chart',
      'xlsx': 'table_chart',
      'ppt': 'slideshow',
      'pptx': 'slideshow',
      'txt': 'text_snippet',
      'zip': 'folder_zip',
      'rar': 'folder_zip',
    };
    
    return iconMap[extension] || 'insert_drive_file';
  }

  /**
   * Format file size for display
   */
  formatFileSize(bytes: number): string {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
  }

  /**
   * Download attachment - MODIFIED to use ApiService
   */
  download(): void {
    if (this.isPending) {
      // For pending files, trigger browser download from the file object
      const file = this.attachment.file;
      if (file) {
        const url = URL.createObjectURL(file);
        const link = document.createElement('a');
        link.href = url;
        link.download = file.name;
        link.click();
        URL.revokeObjectURL(url);
      }
    } else {
      // For existing attachments, use ApiService
      this.apiService.downloadTicketAttachment(this.attachment.id).subscribe(
        (blob: Blob) => {
          const url = URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = url;
          link.download = this.attachment.original_name || this.attachment.file_name || 'download';
          link.click();
          URL.revokeObjectURL(url);
        },
        (error) => {
          console.error('Error downloading attachment:', error);
        }
      );
    }
  }

  /**
   * Close the modal
   */
  close(): void {
    this.dialogRef.close();
  }
}