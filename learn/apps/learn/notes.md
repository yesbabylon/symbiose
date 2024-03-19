# Notes

## Important notes

- Added `"resolveJsonModule": true` and `"allowSyntheticDefaultImports": true` to `tsconfig.json` for importing te assets/menu.json

## Architecture

Ranger les composant dans le dossier `in`.

## TODO

- [x] Créer des composants de routing qui respecte la hiérarchie des entités d'Equal
- [x] Créer une navigation (left panel)

  - Est ce que je doit modifier la logique dans app.root.component.ts ?

    <br>app.root.component.ts:

    ```ts
    public async ngOnInit() {
      // TODO: <AlexisVS> Disabled for development purpose
      // try {
      //     await this.auth.authenticate();
      // }
      // catch(err) {
      //     window.location.href = '/auth';
      //     return;
      // }

      // load menus from server
      this.env.getEnv().then( async (environment:any) => {
          this.app_root_package = 'core';
          const data = await this.api.getMenu(this.app_root_package, 'sandbox.left');
          // store full translated menu
          this.leftMenu = this.translateMenu(data.items, data.translation);
          // fill left pane with unfiltered menu
          this.navMenuItems = this.leftMenu;
          // this.translationsMenuLeft = this.leftMenu.translation;

          const top_menu:any = await this.api.getMenu(this.app_root_package, 'sandbox.top');
          this.topMenuItems = top_menu.items;
          this.translationsMenuTop = top_menu.translation;
      });
    }
    ```

- [ ] Créer un layout pour les pages 'vitrine' ?
- [ ] Créer la view show adapté pour {pack (course), module, chapter (lesson), page }
  - [ ] Layout ?
  - [ ] Type TS pour Pack (Course ?) ?
    - [ ] Type générique ?
