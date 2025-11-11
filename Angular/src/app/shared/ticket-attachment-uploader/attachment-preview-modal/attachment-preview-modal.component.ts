import { Component, Inject, OnInit, OnDestroy } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { ApiService } from 'src/app/servizi/api.service';
import { Subject, takeUntil } from 'rxjs';

export interface AttachmentPreviewData {
  attachment: any;
}

@Component({
  selector: 'app-attachment-preview-modal',
  templateUrl: './attachment-preview-modal.component.html',
  styleUrls: ['./attachment-preview-modal.component.scss'],
  standalone: false
})
export class AttachmentPreviewModalComponent implements OnInit, OnDestroy {
  attachment: any;
  previewUrl: SafeResourceUrl | null = null;
  blobUrl: string | null = null;
  isLoading = true;
  loadError = false;
  previewType: 'image' | 'pdf' | 'text' | 'unsupported' = 'unsupported';

  private destroy$ = new Subject<void>();

  constructor(
    public dialogRef: MatDialogRef<AttachmentPreviewModalComponent>,
    @Inject(MAT_DIALOG_DATA) public data: AttachmentPreviewData,
    private apiService: ApiService,
    private sanitizer: DomSanitizer
  ) {
    this.attachment = data.attachment;
  }

  ngOnInit(): void {
    this.determinePreviewType();
    this.loadPreview();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
    
    // Cleanup blob URL
    if (this.blobUrl) {
      URL.revokeObjectURL(this.blobUrl);
    }
  }

  /**
   * Determine preview type based on file extension
   */
  private determinePreviewType(): void {
    const ext = this.getFileExtension(this.attachment.original_name).toLowerCase();
    
    const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
    const pdfExts = ['pdf'];
    const textExts = ['txt', 'log', 'md', 'json', 'xml', 'csv'];

    if (imageExts.includes(ext)) {
      this.previewType = 'image';
    } else if (pdfExts.includes(ext)) {
      this.previewType = 'pdf';
    } else if (textExts.includes(ext)) {
      this.previewType = 'text';
    } else {
      this.previewType = 'unsupported';
    }
  }

  /**
   * Load preview based on file type
   */
  private loadPreview(): void {
    if (this.previewType === 'unsupported') {
      this.isLoading = false;
      return;
    }

    this.apiService.downloadTicketAttachment(this.attachment.id)
      .pipe(takeUntil(this.destroy$))
      .subscribe(
        (blob: Blob) => {
          this.blobUrl = URL.createObjectURL(blob);
          
          if (this.previewType === 'image') {
            this.previewUrl = this.sanitizer.bypassSecurityTrustResourceUrl(this.blobUrl);
          } else if (this.previewType === 'pdf') {
            // For PDF, we'll use an iframe
            this.previewUrl = this.sanitizer.bypassSecurityTrustResourceUrl(this.blobUrl);
          } else if (this.previewType === 'text') {
            // For text files, read as text
            this.readTextContent(blob);
          }
          
          this.isLoading = false;
        },
        (error) => {
          console.error('Error loading preview:', error);
          this.loadError = true;
          this.isLoading = false;
        }
      );
  }

  /**
   * Read text file content
   */
  private async readTextContent(blob: Blob): Promise<void> {
    try {
      const text = await blob.text();
      // Create a data URL with the text content
      const dataUrl = `data:text/plain;charset=utf-8,${encodeURIComponent(text)}`;
      this.previewUrl = this.sanitizer.bypassSecurityTrustResourceUrl(dataUrl);
    } catch (error) {
      console.error('Error reading text content:', error);
      this.loadError = true;
    }
  }

  /**
   * Download the attachment
   */
  downloadAttachment(): void {
    if (this.blobUrl) {
      const link = document.createElement('a');
      link.href = this.blobUrl;
      link.download = this.attachment.original_name;
      link.click();
    } else {
      // Fallback: request download again
      this.apiService.downloadTicketAttachment(this.attachment.id)
        .pipe(takeUntil(this.destroy$))
        .subscribe(
          (blob: Blob) => {
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = this.attachment.original_name;
            link.click();
            URL.revokeObjectURL(url);
          },
          (error) => {
            console.error('Error downloading:', error);
          }
        );
    }
  }

  /**
   * Close modal
   */
  close(): void {
    this.dialogRef.close();
  }

  /**
   * Get file extension
   */
  getFileExtension(fileName: string): string {
    return fileName.split('.').pop() || '';
  }

  /**
   * Get file icon
   */
  getFileIcon(): string {
    const ext = this.getFileExtension(this.attachment.original_name).toLowerCase();
    
    const iconMap: { [key: string]: string } = {
      'pdf': 'picture_as_pdf',
      'doc': 'description', 'docx': 'description',
      'xls': 'table_chart', 'xlsx': 'table_chart',
      'ppt': 'slideshow', 'pptx': 'slideshow',
      'zip': 'folder_zip', 'rar': 'folder_zip',
      'mp3': 'audiotrack', 'wav': 'audiotrack',
      'mp4': 'videocam', 'avi': 'videocam',
      'default': 'insert_drive_file'
    };

    return iconMap[ext] || iconMap['default'];
  }

  /**
   * Format bytes to human readable
   */
  formatBytes(bytes: number): string {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
  }
}