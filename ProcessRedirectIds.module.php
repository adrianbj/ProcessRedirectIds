<?php

/**
 * ProcessWire Redirect ID based URLs
 * by Adrian Jones
 *
 * Copyright (C) 2020 by Adrian Jones
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 */

class ProcessRedirectIds extends WireData implements Module, ConfigurableModule {

    public static function getModuleInfo() {
        return array(
            'title' => 'Redirect ID based URLs',
            'summary' => 'Redirects ID based URL to full SEO friendly URL',
            'author' => 'Adrian Jones',
            'href' => 'http://modules.processwire.com/modules/process-redirect-ids/',
            'version' => '0.4.0',
            'autoload' => true,
            'singular' => true,
            'icon'     => 'arrow-circle-right'
        );
    }


    /**
     * Data as used by the get/set functions
     *
     */
    protected $data = array();


   /**
     * Default configuration for module
     *
     */
    static public function getDefaultData() {
        return array(
            "redirectType" => "Redirect",
            "enabledFields" => array(),
            "enabledPages" => array(),
            "enabledTemplates" => array(),
            "rewriteLinks" => "",
            "rewriteFormat" => ""
        );
    }

    /**
     * Populate the default config data
     *
     */
    public function __construct() {

        if(PHP_SAPI === 'cli' || !isset($_SERVER['REQUEST_URI'])) return;

        foreach(self::getDefaultData() as $key => $value) {
            $this->$key = $value;
        }

        // determine the URL that wasn't found
        $url = $_SERVER['REQUEST_URI'];

        // if installed in a subdirectory, make $url relative to the directory ProcessWire is installed in
        if($this->wire('config')->urls->root != '/') {
            $url = substr($url, strlen($this->wire('config')->urls->root)-1);
        }
        $url = str_replace("-","/", $url);
        foreach(explode('/', $url) as $part) {
            $part = preg_replace("/[^0-9]/", "", $part);
            if(is_numeric($part) && strlen($part)>=4) {
                $this->id = (int) $part;
                break;
            }
        }

    }


    public function init() {
        if($this->data['rewriteLinks'])	$this->wire()->addHookAfter('Page::path', $this, 'rewriteLinks');
        $this->wire()->addHookAfter('ProcessPageView::pageNotFound', $this, 'redirectIds', array('priority'=>10000));
    }


    public function ready() {

        // we're interested in page editor only
        if($this->wire('page')->process != 'ProcessPageEdit') return;

        // skip changing templates (only target the actual edit form)
        $id = (int)$this->wire('input')->get->id;
        if(!$id) return;

        // wire('page') would be the page with ProcessPageEdit
        // GET parameter id tells the page that's being edited
        $this->editedPage = $this->wire('pages')->get($id);

        // don't even consider system templates
        if($this->editedPage->template->flags & Template::flagSystem) return;

        if($this->isAllowed($this->editedPage) && $this->editedPage->id != 1) {
            $this->wire()->addHookAfter('ProcessPageEdit::buildFormContent', $this, 'addShortURLLinks');
        }
    }


    public function rewriteLinks(HookEvent $e) {

        $page = $e->object;
        if(!isset($this->id) || $page->id != $this->id) {
            if($this->isAllowed($page)) {
                if($page->id!=1 && $page->template != 'admin') {
                    $e->return = @eval('return "'.$this->data['rewriteFormat'].'";');
                }
            }
        }
    }


    public function redirectIds($event) {

        // if there is an ID in the URL
        if(isset($this->id) && $this->id!='') {

            $p = $this->wire('pages')->get($this->id);

            if($this->isAllowed($p)) {
                //1005 is the id of the default PW site-map page. Something about the recursive function in there causing out of memory errors when using the "Load" option.
                if($this->data['redirectType']=='Load' && $p->id != 1005) {
                    header("HTTP/1.1 200 OK");
                    $event->return = str_replace("<head>", "<head>\n\n\t<link href='$p->httpUrl' rel='canonical' />", $p->render());
                }
                else {
                    $this->wire('session')->redirect($p->httpUrl);
                }
            }
        }
    }


    public function addShortURLLinks(HookEvent $event) {

        $current_page = $event->object->getPage();
        $form = $event->return;

        $this->wire('modules')->get('FieldtypeFieldsetTabOpen');
        if(class_exists("\ProcessWire\InputfieldFieldsetTabOpen")) {
            $field = new \ProcessWire\InputfieldFieldsetTabOpen;
        }
        else {
            $field = new InputfieldFieldsetTabOpen;
        }
        $field->name = 'shortlinks';
        $field->label = __('ShortLinks', __FILE__);
        $form->add($field);


        // construct contents inside a container
        $field = $this->modules->get("InputfieldMarkup");
        $field->label = $this->_("List of example shortlinks that can be used to access this page");
        $field->description = $this->_("You can define any URL you want, so long as the page ID ({$current_page->id}) is in the URL somewhere.");
        $field->notes = $this->_("The last two examples are longer than the default title and therefore might seem strange, but their advantage is that if the page is ever renamed, or moved to different parents, these links will still work, as will any of the other options listed here.");

        $baseUrl = "http://{$this->wire('config')->httpHost}{$this->wire('config')->urls->root}";

        $shortlinks = '';
        $shortlinks .= "<p><a href='{$baseUrl}{$current_page->id}/' target='_blank'>{$baseUrl}{$current_page->id}/</a></p>";
        if($current_page->parent->id !=1) $shortlinks .= "<p><a href='".$baseUrl.ltrim($current_page->path, '/')."{$current_page->id}/' target='_blank'>".$baseUrl.ltrim($current_page->path, '/')."{$current_page->id}/</a></p>";
        $shortlinks .= "<p><a href='{$baseUrl}{$current_page->id}{$current_page->path}' target='_blank'>{$baseUrl}{$current_page->id}{$current_page->path}</a></p>";
        $shortlinks .= "<p><a href='".$baseUrl.trim($current_page->path, '/')."-{$current_page->id}/' target='_blank'>".$baseUrl.trim($current_page->path, '/')."-{$current_page->id}/</a></p>";

        $field->value = $shortlinks;
        $form->add($field);


        $this->wire('modules')->get('FieldtypeFieldsetClose');
        if(class_exists("\ProcessWire\InputfieldFieldsetClose")) {
            $field = new \ProcessWire\InputfieldFieldsetClose;
        }
        else {
            $field = new InputfieldFieldsetClose;
        }
        $field->name = "shortlinks_END";
        $form->add($field);

    }


    public function isAllowed($p) {
        if($p->id && $p->id >= 1000 && $p->parent->id != 2 && $p->viewable()) {

            foreach(explode("|", $p->parents) as $parent) {
                $parentEnabled = in_array($parent, $this->data['enabledPages']) ? true : false;
            }

            if((count($this->data['enabledTemplates']) == 0 || in_array($p->template->name, $this->data['enabledTemplates'])) &&
            (count($this->data['enabledPages']) == 0 || in_array($p->id, $this->data['enabledPages'])) || $parentEnabled) {
                return true;
            }
            else {
                return false;
            }
        }
    }


    /**
     * Return an InputfieldsWrapper of Inputfields used to configure the class
     *
     * @param array $data Array of config values indexed by field name
     * @return InputfieldsWrapper
     *
     */
    public function getModuleConfigInputfields(array $data) {

        $data = array_merge(self::getDefaultData(), $data);

        $wrapper = new InputfieldWrapper();

        $f = wire('modules')->get('InputfieldSelect');
        $f->attr('name+id', 'redirectType');
        $f->label = __('Redirect Type', __FILE__);
        $f->description = __('The default is to redirect to the original PW url for the page, but if you prefer you can change this setting to "Load" to have it keep the ID based URL as entered and simply load the page content.', __FILE__);
        $f->addOption("Redirect");
        $f->addOption("Load");
        if(isset($data['redirectType'])) $f->value = $data['redirectType'];
        $wrapper->add($f);

        $f = $this->wire('modules')->get("InputfieldCheckbox");
        $f->attr('name+id', 'rewriteLinks');
        $f->label = __('Rewrite Links', __FILE__);
        $f->description = __('Determines whether to rewrite all links to include the page ID - based on the format defined below.', __FILE__);
        $f->attr('checked', $data['rewriteLinks'] ? 'checked' : '' );
        $f->columnWidth = 50;
        $wrapper->add($f);

        $f = $this->wire('modules')->get("InputfieldText");
        $f->attr('name+id', 'rewriteFormat');
        if(isset($data['rewriteFormat']) && $data['rewriteFormat']!='') $f->value = $data['rewriteFormat'];
        $f->label = __('Rewrite Format', __FILE__);
        $f->description = __('Determines the format for rewriting links', __FILE__);
        $f->notes = __('eg.`/{$page->name}-{$page->id}/` OR `/{$page->id}/`');
        $f->showIf = "rewriteLinks=1";
        $f->requiredIf = "rewriteLinks=1";
        $f->columnWidth = 50;
        $wrapper->add($f);

        $f = $this->wire('modules')->get('InputfieldAsmSelect');
        $f->attr('name+id', 'enabledTemplates');
        $f->label = __('Enabled templates', __FILE__);
        $f->description = __('ID based shortlinks will only work for the selected templates. If no templates are chosen, the links will work for all templates.', __FILE__);
        $f->setAsmSelectOption('sortable', false);
        // populate with all available templates
        foreach(wire('templates') as $t) {
            // filter out system templates
            if(!($t->flags & Template::flagSystem)) $f->addOption($t->name);
        }
        if(isset($data['enabledTemplates'])) $f->value = $data['enabledTemplates'];
        $wrapper->add($f);

        $f = $this->wire('modules')->get('InputfieldPageListSelectMultiple');
        $f->attr('name+id', 'enabledPages');
        $f->label = __('Enabled pages', __FILE__);
        $f->description = __('ID based shortlinks will only work for the selected pages and their children. If no pages are chosen, the links will work for all pages, except admin and other pages that are not viewable to the user.', __FILE__);
        $f->attr('title', __('Enable page', __FILE__));
        if(isset($data['enabledPages'])) $f->value = $data['enabledPages'];
        $wrapper->add($f);

        return $wrapper;
    }
}