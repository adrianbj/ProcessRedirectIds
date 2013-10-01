ProcessRedirectIds
==================

Processwire module for redirecting ID based URL to full SEO friendly URL

##WARNING - please don't use this in production yet - need to look into potential security risks

Very simple module that alllows you to link to pages with their page ID in the URL. The ID can be in any location in the URL.

Will work for all viewable pages by default, but can be limited to specific templates and pages/parents in the module config settings.

A new ShortLinks tab is generated on the edit page providing example links that can be copied.

For example you could do any of the following, where 1058 is the ID of the page you want to load:

http://www.mysite.com/1058

http://www.mysite.com/1058/this-is-the-fancy-title

http://www.mysite.com/category1/category2/1058/any-text/

http://www.mysite.com/article/this-is-the-fancy-title-1058/

Any of those will redirect to the proper URL, eg: http://www.mysite.com/this-is-the-fancy-title/

##Forum:
http://processwire.com/talk/topic/4611-redirect-id-based-urls/


