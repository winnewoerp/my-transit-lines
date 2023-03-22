# My Transit Lines

My Transit Lines is a Wordpress theme that adds a map-based framework for proposing new public transit lines, including vector data (lines and named or unnamed stations), title, description and transit mode. A front-end form is provided to enter such data and new transit line and/or station proposals are being saved as a custom "proposal" Wordpress post type. A tile-layout list shows all existing proposals including transit mode, title, and map.

The basic version was developed in summer 2014 by Johannes Bouchain (Hamburg, Germany) for the so called "Linie Fünf" (Line Five) project in Hamburg, and in fall 2014, the Berlin branch was launched, called "Linie Plus | Berlin". In 2016, the general version "Linie Plus | Extern" has been added, allowing to add proposals in any geographical region.

Until spring 2017, My Transit Lines has been developed locally. Then, the decision was taken to upload the theme to a Github repository for further community-based developments.

## Installation guide

1. Install WordPress

2. Upload the theme as ZIP folder under Design > Themes > "Add Theme" in the WordPress Dashboard or via FTP by extracting the files into /wp-content/themes/ in your WordPress installation.

3. Activate "My Transit Lines" theme under Design > Themes.

4. Adapt the theme settings to your needs by clicking the My Transit Lines light rail icon in the lower part of the left-hand dashboard menu (e.g. §General Settings§ for the logo of your project and more, "Map and category settings" for setting default map position and transport mode categories).

5. Design your start page, e.g. with individuel text and a shortcode block [mtl-tile-list] that will show the tile list of the proposals.

6. Go to "Settings" > "Reading" in the WordPress Dashboard and set your designed start page as the start page of your project.

7. Design other pages for the project, e.g. the poposal form by putting [mtl-proposal-form] shortcode into the page content.

8. **Allow users to register** by checking "Everybody can register" under Settings > General in the WordPress Dashboard.

9. Go to Design > Widgets in the WordPress Dashboard menu and add the "MTL Login/Register" widget to the topmenu section (and remove unnecessary widgets from the "Sidebar" section).

10. Setup your navigation menus under Design > Menus.

## Theme shortcodes

If you are not familiar with WordPress shortcodes, follow this link: [https://codex.wordpress.org/Shortcode](https://codex.wordpress.org/Shortcode).

* [mtl-tile-list] - The list of the existing proposals in a tile list design.

* [mtl-proposal-form] - The form for submitting new proposals.

*(Not yet complete, to be continued.)*





