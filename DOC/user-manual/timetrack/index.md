# Timetrack package

The package proposes a simple way to record the time entries of the employees.
These entries can generate sale receivables who can be invoiced to customers.

## Time entry

A time entry records the duration of time an employee spent working on a task.
Because a time entry is also a sale entry, a receivable can be generated from it.

## Project

A customer project categorizes the time entries, it is also used to apply specific sale models to time entries.

## Time entry sale model

A sale model allows to automatically set the sale information of a new time entry.
When a time entry is created, a sale model is applied if the time entry's origin (backlog/email/support) matches the sale model's origin.
A sale model can be linked to projects, in that case it will only be applied to new time entries of those projects.
