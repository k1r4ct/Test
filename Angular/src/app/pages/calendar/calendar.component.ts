import { Component, EventEmitter, Input, OnInit, Output } from "@angular/core";
import { CalendarView, DAYS_OF_WEEK,CalendarEvent ,CalendarEventTimesChangedEvent } from "angular-calendar";
import { ApiService } from "src/app/servizi/api.service";
import { parseISO, parse, format } from "date-fns";
interface MyEvent {
  day: {
    events: {
      meta: {
        idLead: number;
      };
    }[];
  };
}
@Component({
    selector: "app-calendar",
    templateUrl: "./calendar.component.html",
    styleUrl: "./calendar.component.scss",
    standalone: false
})
export class CalendarComponent implements OnInit {
  view: CalendarView = CalendarView.Month;
  viewDate: Date = new Date();
  events: CalendarEvent[] = [];
  CalendarView = CalendarView; 
  locale: string = "it";
  arrayidLead:MyEvent[]=[];
  @Output() arrayidLeadChange = new EventEmitter<any[]>();
  constructor(private apiService: ApiService) {}
  weekStartsOn: number = DAYS_OF_WEEK.MONDAY; // Set the first day of the week to Monday

  ngOnInit(): void {
    //console.log(this.viewDate);

    this.apiService.getAppointments().subscribe((data: any) => {
      console.log(data);

      this.events = data.body.risposta.map((appointment: any) => {
        const eventDate = parse(
          appointment.data_appuntamento,
          "yyyy-MM-dd HH:mm:ss",
          new Date()
        );
        const note = appointment.note ? ` - Note: ${appointment.note}` : "";
        //console.log("Event Date:", eventDate); // Verifica che la data sia corretta
        return {
          start: eventDate,
          title: `${appointment.nome} ${appointment.cognome}: Note ${
            appointment.note ? ": " + appointment.note : ""
          }`,
          allDay: false, // Cambia a true se l'appuntamento è per l'intera giornata
          meta: {
            note: (appointment.note?appointment.note + " " : " ") + 
            (appointment.telefono? appointment.telefono + " ":" ") + 
            (appointment.email? appointment.email + " ":" "),
            idLead:appointment.id
          },
          draggable:true,
        };
      });

      //console.log("Events:", this.events); // Verifica che gli eventi siano correttamente popolati
    });
  }
  handleEvent(
    action: string,
    event: { event: CalendarEvent; sourceEvent: MouseEvent | KeyboardEvent }
  ): void {
    //console.log("Event action:", action);
    //console.log("Event data:", event.event);
    if (event.event.meta && event.event.meta.note) {
      alert(`Note: ${event.event.meta.note}`);
    }
  }
  setView(view: CalendarView) {
    //console.log(view);
    
    this.view = view;
  }
  eventTimesChanged({
    event,
    newStart,
    newEnd
  }: CalendarEventTimesChangedEvent): void {
    this.events = this.events.map(iEvent => {
      if (iEvent === event) {
        return {
          ...iEvent,
          start: newStart,
          end: newEnd,
        };
      }
      return iEvent;
    });
    this.updateEventOnServer(event, newStart);
  }

  updateEventOnServer(event: CalendarEvent, newStart: Date): void {
    //console.log(event.meta?.idLead);
    //console.log(newStart);
    const idLead = event.meta?.idLead;
    const formattedDate = format(newStart, 'yyyy-MM-dd HH:mm:ss');
    //console.log(formattedDate);
    
    const formData= new FormData();

    formData.append('idLead',idLead);
    formData.append('newDate',formattedDate);
    //console.log(formData);

    this.apiService.updateLead(formData).subscribe((Risposta:any)=>{
      //console.log(Risposta);
      
    })
    
    
    // Logica per aggiornare l'evento nel backend tramite API
    // Esempio: this.apiService.updateAppointment(event.id, newStart).subscribe();
  }
  test(event:any){
    this.arrayidLead=[];
    //console.log(event.day.events);
    event.day.events.map((eventi:any)=>{
      //console.log(eventi.meta.idLead);
      this.arrayidLead.push(eventi.meta.idLead);
    })
    this.arrayidLeadChange.emit(this.arrayidLead);
    //console.log(this.arrayidLead);
    
  }
}
