# Development tasks

<!-- TOC -->
* [Development tasks](#development-tasks)
  * [Right side menu](#right-side-menu)
    * [How to do it](#how-to-do-it)
      * [List of parameters or Classes for making the service](#list-of-parameters-or-classes-for-making-the-service)
<!-- TOC -->

## Right side menu

The right side menu need to be changed in reaction to the user and the current page.

- If guest || learner || teacher ( not his own course): display ``BuyCourseComponent``
  <br><br>
- If learner has buy the course: display some informations related to the course progression
    - ? If the course is not started: display ``StartCourseComponent``
    - ? If the course is started: display ``CourseProgressionComponent``
    - ? If the course is finished: display ``CourseFinishedComponent``
    - ? Bref, il faut que le menu de droite soit dynamique et réagisse en fonction de l'utilisateur et de la page courante et afficher des informations pertinantes sur les quelles je n'ai pas encore réflechi.
      <br><br>
- If teacher (his own course) or admin : display ``CourseManagementComponent``

### How to do it

For changing the component in reaction to the user state and router state, use the router events.

Do it in a service called ``RightSideMenuService``.

#### List of parameters or Classes for making the service

- User role (guest, learner, teacher, admin)
- Course state (not started, started, finished, created)
- Router state (current page)
- User edition mode activated or not for (admin, teacher if he is on his own course)

