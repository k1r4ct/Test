import { HttpHeaders } from "@angular/common/http";
import {
  AfterViewInit,
  Component,
  ElementRef,
  Input,
  OnChanges,
  OnInit,
  SimpleChanges,
  ViewChild,
} from "@angular/core";
import { DropzoneConfigInterface } from "ngx-dropzone-wrapper";
import { ApiService } from "src/app/servizi/api.service";
import { HttpClient, HttpEventType, HttpResponse } from "@angular/common/http";
import { ContrattoService } from "src/app/servizi/contratto.service";

import { filter, switchMap, tap } from "rxjs/operators";
import { DropzoneFile, DropzoneOptions } from "dropzone";
import Dropzone from "dropzone";
import { DropzoneService } from "../servizi/dropzone.service";
import {
  ActivatedRoute,
  Router,
  NavigationEnd,
  NavigationStart,
} from "@angular/router";
import { takeUntil } from "rxjs/operators";
import { Subject } from "rxjs";
@Component({
    selector: "app-dropzone",
    templateUrl: "./dropzone.component.html",
    styleUrls: ["./dropzone.component.scss"],
    standalone: false
})
export class DropzoneComponent implements OnInit, AfterViewInit, OnChanges {
  @ViewChild("myDropzone", { static: false }) myDropzoneRef!: ElementRef;
  dropzoneInstance: Dropzone | null = null;

  @Input() uploadType: "document" | "profile" | "contract" = "document";
  isHidden = true;
  idContratto: any;
  contrattoID: any;
  userId: number | null = null;
  url: string;
  urlUser: string;
  urlRemove: string;
  dropzoneFileName: any;
  DropEnable = true;
  messaggio = `<i class="pi pi-cloud-upload border-2 border-circle p-5 text-8xl text-400 border-400"></i> <p class="mt-4 mb-0 text-dark">Trascina e rilascia i file qui per caricarli</p>`;
  config: DropzoneConfigInterface = {};
  myDropzoneOption: DropzoneOptions = {};
  uploadProgress: number | null = null;
  currentUrl: any;
  nomeCambiato: { [originalFileName: string]: string } = {};
  idContrattoselezionato: any;
  allowFileRemoval: any;

  private destroy$ = new Subject<void>();
  constructor(
    private apiService: ApiService,
    private http: HttpClient,
    private Contratto: ContrattoService,

    private dropzoneService: DropzoneService,
    private route: ActivatedRoute,
    private router: Router
  ) {
    this.url = this.apiService.getApiUrl() + "storeIMG";
    this.urlUser = this.apiService.getApiUrl() + "immagineProfiloUtente";
    this.urlRemove = this.apiService.getApiUrl() + "deleteIMG";
  }

  ngOnInit() {
    this.dropzoneInstance?.on('success', (file: DropzoneFile, response: any) => {
      // Usa this.dropzoneInstance.removeFile() per rimuovere il file
      this.dropzoneInstance?.removeFile(file);
  });
    this.router.events.subscribe((event) => {
      if (event instanceof NavigationStart) {
        this.currentUrl = event.url;
        //this.allowFileRemoval = false;// Aggiorna l'URL corrente all'inizio della navigazione
        //console.log(this.currentUrl);
      }
    });
    this.router.events
      .pipe(filter((event) => event instanceof NavigationEnd)) // Filtra solo gli eventi NavigationEnd
      .subscribe(() => {
        this.dropzoneService.destroyDropzone();
        //this.currentUrl = window.location.href; // Aggiorna l'URL
        //console.log(this.currentUrl);
      });

    if (this.uploadType === "profile") {
      this.messaggio = `<i class="pi pi-cloud-upload border-2 border-circle p-5 text-8xl text-400 border-400"></i>
                <p class="mt-4 mb-0 text-dark">Trascina e rilascia i file qui per caricarli.</p>`;
    }

    this.Contratto.getContratto().subscribe((oggetto) => {
      this.idContratto = oggetto.id_contratto;
      if (this.uploadType === "document" && !this.idContratto) {
        this.DropEnable = false;
        this.isHidden = true;
      } else {
        this.DropEnable = true;
        this.isHidden = false;
      }

      this.config = {
        url: this.apiService.getApiUrl() + "attesaCaricamentoImmagini",
        headers: this.getHeadersObject(
          this.apiService.getAuthHeaders().append("Accept", "application/json")
        ),
        maxFilesize: 10,
        acceptedFiles:
          this.uploadType === "document"
            ? "image/*,application/pdf"
            : "image/*,application/pdf",
        addRemoveLinks: true,
        method: "post",
        clickable: this.DropEnable,
        dictDefaultMessage: this.messaggio,
      };
    });

    setTimeout(() => {
      if (this.myDropzoneRef.nativeElement) {
        this.dropzoneInstance = new Dropzone(
          this.myDropzoneRef.nativeElement,
          this.config
        );
        this.dropzoneService.setDropzoneInstance(this.dropzoneInstance);
        //console.log(this.dropzoneInstance);
      }
    });
  }
  ngOnChanges(changes: SimpleChanges) {
    //console.log(changes);

    if (changes["idContrattoselezionato"] && this.dropzoneInstance) {
      this.fetchAndDisplayExistingFiles();
    }
  }
  fetchAndDisplayExistingFiles() {
    const contrattoId = this.idContrattoselezionato || this.idContratto;

    if (contrattoId) {
      this.apiService.getFilesForContract(contrattoId).subscribe(
        (response: any) => {
          const files = response.body?.risposta?.[contrattoId];

          if (Array.isArray(files) && files.length > 0) {
            files.forEach((fileData: any) => {
              const existingFile = this.dropzoneInstance
                ?.getAcceptedFiles()
                .find((f) => f.name === fileData.name);
              //console.log(existingFile);

              if (!existingFile) {
                this.dropzoneInstance?.emit("addedfile", fileData);
                this.dropzoneInstance?.emit("complete", fileData);

                // Gestione dell'anteprima con l'evento 'thumbnail'
                this.dropzoneInstance?.on("thumbnail", (file, dataUrl) => {
                  if (file.name === fileData.name) {
                    const previewElement = file.previewElement; // Ottieni l'elemento di anteprima
                    if (previewElement) {
                      // Aggiungi elementi al div dell'anteprima
                      const removeButton = document.createElement("button");
                      removeButton.textContent = "Rimuovi";
                      removeButton.addEventListener("click", () => {
                        this.RimuoviFile(file); // Chiama il tuo metodo RimuoviFile
                      });
                      previewElement.appendChild(removeButton);
                    }
                  }
                });

                if (
                  this.dropzoneInstance?.options.createImageThumbnails &&
                  fileData.type.startsWith("image/")
                ) {
                  this.dropzoneInstance?.createThumbnailFromUrl(
                    fileData,
                    fileData.pathfull
                  );
                } else {
                  this.dropzoneInstance?.emit("thumbnail", fileData, null);
                }

                if (fileData.newFileName) {
                  this.nomeCambiato[fileData.name] = fileData.newFileName;
                }
              }
            });
          } else {
            console.warn("Nessun file trovato o formato dati non valido.");
          }
        },
        (error: any) =>
          console.error("Errore durante il recupero dei file:", error)
      );
    }
  }
  ngAfterViewInit(): void {
    if (this.uploadType === "contract") {
      this.messaggio = `<i class="pi pi-cloud-upload border-2 border-circle p-5 text-8xl text-400 border-400"></i>
                <p class="mt-4 mb-0 text-dark">Trascina e rilascia i file qui per caricarli.</p>`;

      // Sottoscriviti al Subject dell'ID del contratto selezionato
      this.Contratto.idContrattoSelezionato$.subscribe((id: any) => {
        if (id) {
          //console.log(id);
          // Verifica se l'ID è valido
          this.idContrattoselezionato = id;
          this.dropzoneInstance?.removeAllFiles(true); // Carica i file esistenti
        } else {
          // Gestisci il caso in cui l'ID del contratto sia null (ad esempio, pulisci Dropzone)
          this.dropzoneInstance?.removeAllFiles(true); // Rimuovi tutti i file da Dropzone
        }
      });
    }
  }

  private getHeadersObject(headers: HttpHeaders): { [key: string]: string } {
    const headersObject: { [key: string]: string } = {};
    headers.keys().forEach((key) => {
      headersObject[key] = headers.get(key) as string;
    });
    return headersObject;
  }
  onUploadError(event: any): void {
    console.error("Errore durante il caricamento:", event);
    // Gestisci l'errore (mostra un messaggio all'utente, ecc.)
  }

  onUploadSuccess(event: any): void {
    //console.log("Caricamento completato:", event);
    this.dropzoneFileName = event[0];
    // Gestisci il successo (aggiorna l'interfaccia, salva informazioni nel database, ecc.)
  }

  cancelUpload(): void {
    // Implementa la logica per annullare il caricamento in corso
    // Ad esempio, puoi utilizzare il metodo disable() di Dropzone
    // e resettare la barra di progresso
  }

  onFileSelected(file: File) {
    //console.log(this.uploadType);
    //console.log(this.idContratto);
    //console.log(file);

    const formData = new FormData();

    if (file) {
      //console.log(file);
      formData.append("file", file);

      if (this.uploadType === "document") {
        this.Contratto.getContratto().subscribe((oggetto) => {
          //console.log(oggetto);

          this.contrattoID = oggetto.id_contratto;
          formData.append("nameFile", file.name);
          formData.append("idContratto", this.idContratto);
          if (this.idContratto && this.idContratto != null) {
            this.http
              .post(this.url, formData, {
                /* ... */
              })
              .subscribe((risp: any) => {
                //console.log(risp);
                //console.log("type Document");
              });
          }
          this.dropzoneInstance?.on("queuecomplete", () => {
            this.dropzoneInstance?.removeAllFiles(true);
          });
        });
      } else if (this.uploadType === "profile") {
        formData.append("nameFile", file.name);

        this.http
          .post(this.url, formData, {
            /* ... */
          })
          .subscribe((risp: any) => {
            //console.log(risp);
            //console.log("type Profilo");
          });
      } else if (this.uploadType === "contract") {
        this.Contratto.getContratto().subscribe((Contratto: any) => {
          //console.log(Contratto);
          this.idContrattoselezionato = Contratto.id_contratto;
        });
        if (
          this.idContrattoselezionato &&
          this.idContrattoselezionato != null
        ) {
          formData.append("nameFile", file.name);
          formData.append("idContratto", this.idContrattoselezionato);
          this.http
            .post(this.url, formData, {
              /* ... */
            })
            .subscribe((risp: any) => {
              //console.log(risp);
              //console.log("type contract");
            });
        }
      }
    }
    //console.log(file);
    //console.log(this.dropzoneInstance);
    
    this.dropzoneInstance?.on("success", () => {
      this.dropzoneInstance?.removeAllFiles(true);
    });
    //console.log(file);
    //console.log(this.dropzoneInstance);

  }

  RimuoviFile(file: File) {
    if (this.allowFileRemoval) {
      //console.log(window.location.href);

      if (
        window.location.href == "http://localhost:4200/clienti" ||
        window.location.href == "http://localhost:4200/contratti"
      ) {
        if (file) {
          const formData = new FormData();
          formData.append("file", file);
          formData.append("nameFile", this.nomeCambiato[file.name]);

          if (this.uploadType === "document") {
            this.Contratto.getContratto()
              .pipe(takeUntil(this.destroy$))
              .subscribe((oggetto) => {
                this.contrattoID = oggetto.id_contratto;
                formData.append("idContratto", this.idContratto);

                this.http
                  .post(this.urlRemove, formData, {
                    /* ... */
                  })
                  .subscribe(/* ... */);
              });
          } else if (this.uploadType === "profile" && this.userId) {
            formData.append("userId", this.userId.toString());

            this.http
              .post(
                this.apiService.getApiUrl() + "deleteProfileImage",
                formData,
                {
                  /* ... */
                }
              )
              .subscribe(/* ... */);
          } else if (this.uploadType === "contract") {
            this.Contratto.getContratto()
              .pipe(takeUntil(this.destroy$))
              .subscribe((Contratto: any) => {
                //console.log(Contratto);
                this.idContrattoselezionato = Contratto.id_contratto;
              });
            if (this.idContrattoselezionato) {
              formData.append("nameFile", file.name);
              formData.append("idContratto", this.idContrattoselezionato);
              this.apiService.deleteIMG(formData).subscribe((Risposta: any) => {
                //console.log(Risposta);
              });
            }
          }
        }
      } else {
        //console.log("nessun file rimosso");
      }
    }
  }
  ngOnDestroy() {
    this.destroy$.next(); // Emetti il segnale di distruzione
    this.destroy$.complete(); // Completa il Subject
  }
}
