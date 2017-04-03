Registered Nav Menu Location Stats
==================================

For each slurped theme, the sidebars registered can be parsed out and written into a JSON file in the `data` subdirectory.


```bash
../../update
php ./parse-themes.php
cat data/twentythirteen.json
```

Which will output:

```json
{
    "sidebar-1": {
        "name": "__(Main Widget Area,twentythirteen)",
        "id": "sidebar-1",
        "description": "__(Appears in the footer section of the site.,twentythirteen)",
        "before_widget": "<aside id=\"%1$s\" class=\"widget %2$s\">",
        "after_widget": "<\/aside>",
        "before_title": "<h3 class=\"widget-title\">",
        "after_title": "<\/h3>"
    },
    "sidebar-2": {
        "name": "__(Secondary Widget Area,twentythirteen)",
        "id": "sidebar-2",
        "description": "__(Appears on posts and pages in the sidebar.,twentythirteen)",
        "before_widget": "<aside id=\"%1$s\" class=\"widget %2$s\">",
        "after_widget": "<\/aside>",
        "before_title": "<h3 class=\"widget-title\">",
        "after_title": "<\/h3>"
    }
}
```

Statistics for the registered sidebars can then be generated via:

```bash
./php generate-stats.php
```

Stats have been copied into a [Google Sheet](https://docs.google.com/spreadsheets/d/1sjm95-P7ocEL1m1TlAToL83TLNEijo9RKyBmERMA5hs/edit).


### Credits/License ###

Author: Weston Ruter, XWP

Copyright (c) 2017 XWP (https://xwp.co/)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2 or, at
your discretion, any later version, as published by the Free
Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
