import { Component, Input, Output, EventEmitter, OnInit, OnDestroy, OnChanges, SimpleChanges } from '@angular/core';
import { FilePondModule } from 'ngx-filepond';
import { HttpClient } from '@angular/common/http';
import { ApiService } from 'src/app/servizi/api.service';
import { ContrattoService } from 'src/app/servizi/contratto.service';
import { Subject, takeUntil } from 'rxjs';
import * as FilePond from 'filepond';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';

// Registrazione plugin (funzione accetta plugin come default exports)
(FilePond as any).registerPlugin(FilePondPluginFileValidateType, FilePondPluginImagePreview);

@Component({
  selector: 'app-filepond-uploader',
  templateUrl: './filepond-uploader.component.html',
  styleUrls: ['./filepond-uploader.component.scss'],
  standalone: true,
  imports: [FilePondModule]
})
export class FilepondUploaderComponent implements OnInit, OnDestroy, OnChanges {
  @Input() uploadType: 'document' | 'profile' | 'contract' = 'document';
  @Input() maxFileSize: string = '10MB';
  @Input() acceptedFileTypesOverride: string[] | null = null; // se valorizzato sostituisce la scelta automatica
  @Input() replaceOnProfileUpload: boolean = true; // se true sostituisce l'immagine profilo dopo upload
  @Output() uploaded = new EventEmitter<any>();
  @Output() removed = new EventEmitter<any>();
  @Output() error = new EventEmitter<any>();

  pondOptions: any;
  pondFiles: any[] = [];
  currentContractId: number | null = null;
  private destroy$ = new Subject<void>();

  private readonly uploadEndpoint = this.apiService.getApiUrl() + 'storeIMG';
  private readonly deleteEndpoint = this.apiService.getApiUrl() + 'deleteIMG';
  private readonly deleteProfileEndpoint = this.apiService.getApiUrl() + 'deleteProfileImage';

  constructor(
    private apiService: ApiService,
    private http: HttpClient,
    private contrattoService: ContrattoService
  ) {}

  ngOnInit(): void {
    // Se NON è upload profilo, recupero contratto corrente e carico file esistenti
    if (this.uploadType !== 'profile') {
      this.contrattoService.getContratto()
        .pipe(takeUntil(this.destroy$))
        .subscribe((c: any) => {
          if (c?.id_contratto) {
            this.currentContractId = c.id_contratto;
            this.loadExistingFiles();
          }
        });
    } else {
      // Assicuro che nessun file precedente resti visualizzato passando array vuoto
      this.pondFiles = [];
    }

    this.pondOptions = {
      allowMultiple: this.uploadType !== 'profile',
      maxFileSize: this.maxFileSize,
      acceptedFileTypes: this.resolveAcceptedTypes(),
      labelIdle: 'Trascina i file o <span class="filepond--label-action"> Sfoglia </span>',
      server: {
        process: (fieldName: string, file: File, metadata: any, load: any, error: any, progress: any, abort: any) => {
          const formData = new FormData();
          formData.append('file', file, file.name);
          formData.append('nameFile', file.name);
          if (this.currentContractId && (this.uploadType === 'document' || this.uploadType === 'contract')) {
            formData.append('idContratto', String(this.currentContractId));
          }
          const sub = this.http.post(this.uploadEndpoint, formData, {
            reportProgress: true,
            observe: 'events',
            headers: this.apiService.getAuthHeaders()
          }).pipe(takeUntil(this.destroy$)).subscribe({
            next: (evt: any) => {
              if (evt.type === 1 && evt.total) {
                progress(true, evt.loaded, evt.total);
              } else if (evt.body) {
                const newName = evt?.body?.newFileName || file.name;
                load(newName);
                this.uploaded.emit({ original: file.name, stored: newName, body: evt.body });
                if (this.uploadType === 'profile' && this.replaceOnProfileUpload) {
                  // Sostituisci il file visibile con quello appena caricato (mostra solo uno)
                  this.pondFiles = [
                    {
                      source: newName,
                      options: { type: 'local', metadata: { originalName: file.name } }
                    }
                  ];
                }
              }
            },
            error: (e) => { error('Errore upload'); this.error.emit(e); }
          });
          return { abort: () => { sub.unsubscribe(); abort(); } };
        },
        revert: (uniqueFileId: string, load: any, error: any) => {
          const formData = new FormData();
          formData.append('nameFile', uniqueFileId);
          if (this.uploadType === 'profile') {
            this.http.post(this.deleteProfileEndpoint, formData, { headers: this.apiService.getAuthHeaders() })
              .pipe(takeUntil(this.destroy$))
              .subscribe({ next: () => { this.removed.emit(uniqueFileId); load(); }, error: (e) => { error('Errore remove'); this.error.emit(e); } });
          } else {
            if (this.currentContractId) formData.append('idContratto', String(this.currentContractId));
            this.http.post(this.deleteEndpoint, formData, { headers: this.apiService.getAuthHeaders() })
              .pipe(takeUntil(this.destroy$))
              .subscribe({ next: () => { this.removed.emit(uniqueFileId); load(); }, error: (e) => { error('Errore remove'); this.error.emit(e); } });
          }
        },
        load: (source: string, load: any) => { load(null); }
      }
    };

    if (this.uploadType !== 'profile') {
      this.contrattoService.idContrattoSelezionato$
        .pipe(takeUntil(this.destroy$))
        .subscribe((id: any) => {
          if (id) {
            this.currentContractId = id;
            this.loadExistingFiles();
          }
        });
    }
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['uploadType'] && !changes['uploadType'].firstChange) {
      // Reset completo quando cambia tipo
      this.pondFiles = [];
      this.currentContractId = null;
      // Aggiorna dinamicamente alcune opzioni
      if (this.pondOptions) {
        this.pondOptions.allowMultiple = this.uploadType !== 'profile';
        this.pondOptions.acceptedFileTypes = this.resolveAcceptedTypes();
      }
    }
    if (changes['acceptedFileTypesOverride'] && this.pondOptions) {
      this.pondOptions.acceptedFileTypes = this.resolveAcceptedTypes();
    }
  }

  private resolveAcceptedTypes(): string[] {
    if (this.acceptedFileTypesOverride && this.acceptedFileTypesOverride.length) {
      return this.acceptedFileTypesOverride;
    }
    if (this.uploadType === 'profile') {
      return ['image/*'];
    }
    return ['image/*', 'application/pdf'];
  }

  private loadExistingFiles(): void {
    if (!this.currentContractId || this.uploadType === 'profile') return; // mai caricare file contratto in modalità profilo
    this.apiService.getFilesForContract(this.currentContractId)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (resp: any) => {
          const contractId = this.currentContractId!;
            const risposta = resp?.body?.risposta;
            const list = risposta ? (risposta[contractId] || risposta[String(contractId)] || []) : [];
            if (Array.isArray(list)) {
              this.pondFiles = list.map((f: any) => ({
                source: f.newFileName || f.name,
                options: { type: 'local', metadata: { originalName: f.name, pathfull: f.pathfull, mime: f.type } }
              }));
            }
        },
        error: (e) => this.error.emit(e)
      });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }
}