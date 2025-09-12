import { trigger, transition, style, animate } from '@angular/animations';
import { Component } from '@angular/core';

@Component({
    selector: 'icons-cmp',
    moduleId: module.id,
    templateUrl: 'icons.component.html',
    styleUrl: 'icon.component.scss',
    animations: [
        trigger("pageTransition", [
            transition(":enter,:leave", [
                style({ opacity: 0, transform: "scale(0.1)" }), // Inizia piccolo al centro
                animate("500ms ease-in-out", style({ opacity: 1, transform: "scale(1)" })) // Espandi e rendi visibile
            ]),
            transition(":leave", [
                style({ opacity: 1, transform: "scale(1)" }),
                animate("500ms ease-in-out", style({ opacity: 0, transform: "scale(0.1)" })) // Riduci e rendi invisibile
            ])
        ])
    ],
    standalone: false
})

export class IconsComponent{

    state:any;
}
