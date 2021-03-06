Registered Sidebar Stats
========================

For each slurped theme, the sidebars registered can be parsed out and written into a JSON file in the `data` subdirectory.


```bash
../../update
php ./parse-themes.php
cat data/twentyfourteen.json
```

Which will output:

```json
{
    "primary": "__(Top primary menu,twentyfourteen)",
    "secondary": "__(Secondary menu in left sidebar,twentyfourteen)"
}
```

Statistics for the registered sidebars can then be generated via:

```bash
./php generate-stats.php
```

Stats have been copied into a [Google Sheet](https://docs.google.com/spreadsheets/d/1QCormQoVGlI8rKxgn0ylxEw4JAa71372wdoJwgLvENs/edit).


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
