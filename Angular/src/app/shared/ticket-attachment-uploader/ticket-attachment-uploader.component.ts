import { Component, Input, Output, EventEmitter, OnInit, OnDestroy } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ApiService } from 'src/app/servizi/api.service';
import { Subject, takeUntil } from 'rxjs';
import { AttachmentPreviewModalComponent } from './attachment-preview-modal/attachment-preview-modal.component';

export interface TicketAttachment {
  id: number;
  ticket_id: number;
  ticket_message_id?: number;
  user_id: number;
  file_name: string;
  original_name: string;
  file_path: string;
  file_size: number;
  mime_type: string;
  hash: string;
  created_at: string;
  user_name?: string;
  formatted_size?: string;
  is_image?: boolean;
  is_pdf?: boolean;
  is_document?: boolean;
}

export interface SelectedFile {
  file: File;
  preview?: string;
  uploading?: boolean;
  uploaded?: boolean;
  error?: string;
  progress?: number;
}

@Component({
  selector: 'app-ticket-attachment-uploader',
  templateUrl: './ticket-attachment-uploader.component.html',
  styleUrls: ['./ticket-attachment-uploader.component.scss'],
  standalone: false
})
export class TicketAttachmentUploaderComponent implements OnInit, OnDestroy {
  // Configuration inputs
  @Input() ticketId: number | null = null;
  @Input() messageId: number | null = null;
  @Input() variant: 'compact' | 'full' = 'full';
  @Input() maxFiles: number = 5;
  @Input() maxFileSize: number = 10 * 1024 * 1024; // 10MB
  @Input() showExistingAttachments: boolean = true;
  @Input() allowDelete: boolean = true;

  // Output events
  @Output() filesSelected = new EventEmitter<File[]>();
  @Output() uploadComplete = new EventEmitter<TicketAttachment[]>();
  @Output() uploadError = new EventEmitter<string>();
  @Output() fileDeleted = new EventEmitter<number>();

  // State
  selectedFiles: SelectedFile[] = [];
  existingAttachments: TicketAttachment[] = [];
  isDragging = false;
  isUploading = false;
  currentUser: any;

  // Blocked extensions for security
  private blockedExtensions = [
    'exe', 'bat', 'cmd', 'sh', 'php', 'js',
    'jar', 'app', 'deb', 'rpm', 'dmg', 'pkg',
    'com', 'scr', 'vbs', 'msi', 'dll'
  ];

  private destroy$ = new Subject<void>();

  constructor(
    private apiService: ApiService,
    private dialog: MatDialog,
    private snackBar: MatSnackBar
  ) {}

  ngOnInit(): void {
    this.loadCurrentUser();
    if (this.ticketId && this.showExistingAttachments) {
      this.loadExistingAttachments();
    }
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
    // Revoke object URLs to prevent memory leaks
    this.selectedFiles.forEach(sf => {
      if (sf.preview) {
        URL.revokeObjectURL(sf.preview);
      }
    });
  }

  /**
   * Load current user info
   */
  private loadCurrentUser(): void {
    this.apiService.PrendiUtente()
      .pipe(takeUntil(this.destroy$))
      .subscribe(
        (user: any) => {
          this.currentUser = user;
        },
        (error) => {
          console.error('Error loading current user:', error);
        }
      );
  }

  /**
   * Load existing attachments for the ticket
   */
  loadExistingAttachments(): void {
    if (!this.ticketId) return;

    this.apiService.getTicketAttachments(this.ticketId)
      .pipe(takeUntil(this.destroy$))
      .subscribe(
        (response: any) => {
          if (response.response === 'ok' && response.body?.attachments) {
            this.existingAttachments = response.body.attachments;
          }
        },
        (error) => {
          console.error('Error loading attachments:', error);
        }
      );
  }

  /**
   * Handle drag over event
   */
  onDragOver(event: DragEvent): void {
    event.preventDefault();
    event.stopPropagation();
    this.isDragging = true;
  }

  /**
   * Handle drag leave event
   */
  onDragLeave(event: DragEvent): void {
    event.preventDefault();
    event.stopPropagation();
    this.isDragging = false;
  }

  /**
   * Handle drop event
   */
  onDrop(event: DragEvent): void {
    event.preventDefault();
    event.stopPropagation();
    this.isDragging = false;

    const files = event.dataTransfer?.files;
    if (files && files.length > 0) {
      this.handleFiles(Array.from(files));
    }
  }

  /**
   * Handle file input change
   */
  onFileInputChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
      this.handleFiles(Array.from(input.files));
    }
    // Reset input to allow selecting same file again
    input.value = '';
  }

  /**
   * Handle selected files
   */
  private handleFiles(files: File[]): void {
    // Check max files limit
    const totalFiles = this.selectedFiles.length + files.length;
    if (totalFiles > this.maxFiles) {
      this.snackBar.open(
        `Puoi caricare massimo ${this.maxFiles} file`,
        'Chiudi',
        { duration: 3000, panelClass: ['warning-snackbar'] }
      );
      return;
    }

    // Validate and add each file
    files.forEach(file => {
      const validation = this.validateFile(file);
      if (validation.valid) {
        const selectedFile: SelectedFile = {
          file: file,
          uploading: false,
          uploaded: false
        };

        // Create preview for images
        if (this.isImage(file)) {
          selectedFile.preview = URL.createObjectURL(file);
        }

        this.selectedFiles.push(selectedFile);
      } else {
        this.snackBar.open(
          validation.error || 'File non valido',
          'Chiudi',
          { duration: 3000, panelClass: ['error-snackbar'] }
        );
      }
    });

    // Emit selected files
    this.filesSelected.emit(this.selectedFiles.map(sf => sf.file));
  }

  /**
   * Validate file
   */
  private validateFile(file: File): { valid: boolean; error?: string } {
    // Check file size
    if (file.size > this.maxFileSize) {
      return {
        valid: false,
        error: `Il file "${file.name}" supera la dimensione massima di ${this.formatBytes(this.maxFileSize)}`
      };
    }

    // Check blocked extensions
    const extension = this.getFileExtension(file.name).toLowerCase();
    if (this.blockedExtensions.includes(extension)) {
      return {
        valid: false,
        error: `Il tipo di file ".${extension}" non è consentito per motivi di sicurezza`
      };
    }

    return { valid: true };
  }

  /**
   * Remove selected file
   */
  removeSelectedFile(index: number): void {
    const selectedFile = this.selectedFiles[index];
    
    // Revoke preview URL
    if (selectedFile.preview) {
      URL.revokeObjectURL(selectedFile.preview);
    }

    this.selectedFiles.splice(index, 1);
    this.filesSelected.emit(this.selectedFiles.map(sf => sf.file));
  }

  /**
   * Upload all selected files
   */
  async uploadFiles(): Promise<void> {
    if (!this.ticketId) {
      this.snackBar.open(
        'Ticket ID mancante',
        'Chiudi',
        { duration: 3000, panelClass: ['error-snackbar'] }
      );
      return;
    }

    if (this.selectedFiles.length === 0) {
      return;
    }

    this.isUploading = true;

    const formData = new FormData();
    formData.append('ticket_id', this.ticketId.toString());
    
    if (this.messageId) {
      formData.append('message_id', this.messageId.toString());
    }

    // Add all files
    this.selectedFiles.forEach((sf, index) => {
      formData.append('attachments[]', sf.file);
      sf.uploading = true;
    });

    this.apiService.uploadTicketAttachments(formData)
      .pipe(takeUntil(this.destroy$))
      .subscribe(
        (response: any) => {
          this.isUploading = false;

          if (response.response === 'ok') {
            // Mark all as uploaded
            this.selectedFiles.forEach(sf => {
              sf.uploading = false;
              sf.uploaded = true;
            });

            this.snackBar.open(
              `${this.selectedFiles.length} file caricati con successo`,
              'Chiudi',
              { duration: 3000, panelClass: ['success-snackbar'] }
            );

            // Emit upload complete
            if (response.body?.attachments) {
              this.uploadComplete.emit(response.body.attachments);
            }

            // Reload existing attachments
            if (this.showExistingAttachments) {
              this.loadExistingAttachments();
            }

            // Clear selected files after a delay
            setTimeout(() => {
              this.selectedFiles = [];
            }, 1000);
          } else {
            this.handleUploadError('Errore durante il caricamento');
          }
        },
        (error) => {
          this.isUploading = false;
          this.selectedFiles.forEach(sf => sf.uploading = false);
          
          const errorMessage = error.error?.message || 'Errore durante il caricamento dei file';
          this.handleUploadError(errorMessage);
        }
      );
  }

  /**
   * Handle upload error
   */
  private handleUploadError(message: string): void {
    this.snackBar.open(
      message,
      'Chiudi',
      { duration: 4000, panelClass: ['error-snackbar'] }
    );
    this.uploadError.emit(message);
  }

  /**
   * Preview existing attachment
   */
  previewAttachment(attachment: TicketAttachment): void {
    this.dialog.open(AttachmentPreviewModalComponent, {
      width: '90vw',
      maxWidth: '1200px',
      height: '90vh',
      data: { attachment }
    });
  }

  /**
   * Download attachment
   */
  downloadAttachment(attachment: TicketAttachment): void {
    this.apiService.downloadTicketAttachment(attachment.id)
      .pipe(takeUntil(this.destroy$))
      .subscribe(
        (blob: Blob) => {
          const url = window.URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = url;
          link.download = attachment.original_name;
          link.click();
          window.URL.revokeObjectURL(url);

          this.snackBar.open(
            'Download avviato',
            'Chiudi',
            { duration: 2000, panelClass: ['success-snackbar'] }
          );
        },
        (error) => {
          this.snackBar.open(
            'Errore durante il download',
            'Chiudi',
            { duration: 3000, panelClass: ['error-snackbar'] }
          );
        }
      );
  }

  /**
   * Delete existing attachment
   */
  deleteAttachment(attachment: TicketAttachment): void {
    if (!this.allowDelete) return;

    // Check permissions (only admin or uploader can delete)
    const userRole = this.currentUser?.role?.id;
    const isAdmin = [1, 6].includes(userRole);
    const isUploader = attachment.user_id === this.currentUser?.id;

    if (!isAdmin && !isUploader) {
      this.snackBar.open(
        'Non hai i permessi per eliminare questo allegato',
        'Chiudi',
        { duration: 3000, panelClass: ['error-snackbar'] }
      );
      return;
    }

    if (!confirm(`Eliminare "${attachment.original_name}"?`)) {
      return;
    }

    this.apiService.deleteTicketAttachment(attachment.id)
      .pipe(takeUntil(this.destroy$))
      .subscribe(
        (response: any) => {
          if (response.response === 'ok') {
            this.snackBar.open(
              'Allegato eliminato',
              'Chiudi',
              { duration: 2000, panelClass: ['success-snackbar'] }
            );

            // Remove from list
            this.existingAttachments = this.existingAttachments.filter(
              a => a.id !== attachment.id
            );

            this.fileDeleted.emit(attachment.id);
          }
        },
        (error) => {
          this.snackBar.open(
            'Errore durante l\'eliminazione',
            'Chiudi',
            { duration: 3000, panelClass: ['error-snackbar'] }
          );
        }
      );
  }

  /**
   * Get file icon based on extension
   */
  getFileIcon(fileName: string): string {
    const ext = this.getFileExtension(fileName).toLowerCase();
    
    const iconMap: { [key: string]: string } = {
      // Images
      'jpg': 'image', 'jpeg': 'image', 'png': 'image', 'gif': 'image',
      'bmp': 'image', 'svg': 'image', 'webp': 'image',
      // Documents
      'pdf': 'picture_as_pdf',
      'doc': 'description', 'docx': 'description',
      'xls': 'table_chart', 'xlsx': 'table_chart',
      'ppt': 'slideshow', 'pptx': 'slideshow',
      'txt': 'text_snippet',
      // Archives
      'zip': 'folder_zip', 'rar': 'folder_zip', '7z': 'folder_zip',
      // Default
      'default': 'insert_drive_file'
    };

    return iconMap[ext] || iconMap['default'];
  }

  /**
   * Check if file is an image
   */
  isImage(file: File | string): boolean {
    const fileName = typeof file === 'string' ? file : file.name;
    const ext = this.getFileExtension(fileName).toLowerCase();
    return ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'].includes(ext);
  }

  /**
   * Get file extension
   */
  getFileExtension(fileName: string): string {
    return fileName.split('.').pop() || '';
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

  /**
   * Can user delete this attachment?
   */
  canDeleteAttachment(attachment: TicketAttachment): boolean {
    if (!this.allowDelete) return false;
    if (!this.currentUser) return false;

    const userRole = this.currentUser.role?.id;
    const isAdmin = [1, 6].includes(userRole);
    const isUploader = attachment.user_id === this.currentUser.id;

    return isAdmin || isUploader;
  }
}