import { Component, OnInit, Output, Input, EventEmitter } from '@angular/core';
import { Router } from '@angular/router';

@Component({
  selector: 'app-sidebar',
  templateUrl: './sidebar.component.html',
  styleUrls: ['./sidebar.component.scss']
})
export class AppSideBarComponent implements OnInit {
  @Output() toggleMenu = new EventEmitter();
  @Output() toggleBar = new EventEmitter();
  @Input() section: string;
  @Input() items: any[];

  constructor(private router: Router) { }

  ngOnInit(): void {
  }

  
}
