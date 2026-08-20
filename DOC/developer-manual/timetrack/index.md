# Timetrack

This package allows to record the time employees spend working for customers.
Invoices can then be generated to bill customers per hour.

## Time entry

A time entry records the duration of time an employee spent working on a task.
It is an extension of a sale entry, so it can generate receivables that are used to invoice a customer.

## Project

A customer project categorizes the time entries, it is also used to apply specific sale models to time entries.

## Time entry sale model

A sales model streamlines the process of creating time entries by automatically assigning sale information to each new entry.
When a project is associated with a sales model, all time entries for that project will inherit the sale information based on the linked sales model.
