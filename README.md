ProcessRedirectIds
==================

Processwire module for redirecting ID based URL to full SEO friendly URL

Module that alllows you to link to pages with their page ID in the URL. The ID can be in any location in the URL.

It also has an option to automatically rewrite links to defined custom format, so long as you include $page->id in the format.

Will work for all user viewable pages by default, but can be limited to specific templates and pages/parents in the module config settings.

A new ShortLinks tab is generated on the edit page providing example links that can be copied.

For example you could do any of the following, where 1058 is the ID of the page you want to load:

http://www.mysite.com/1058

http://www.mysite.com/1058/this-is-the-fancy-title

http://www.mysite.com/category1/category2/1058/any-text/

http://www.mysite.com/article/this-is-the-fancy-title-1058/

Any of those will redirect to the proper URL, eg: http://www.mysite.com/this-is-the-fancy-title/

There is a config option to simply load the content to the ID based URL, rather than redirecting to the original PW url if you prefer.

##Forum:
http://processwire.com/talk/topic/4611-redirect-id-based-urls/


## License

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

(See included LICENSE file for full license text.)