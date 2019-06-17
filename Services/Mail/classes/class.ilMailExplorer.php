<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class Mail Explorer
 * class for explorer view for mailboxes
 * @author  Stefan Meyer <meyer@leifos.com>
 * @version $Id$
 */
class ilMailExplorer implements \ILIAS\UI\Component\Tree\TreeRecursion
{
    /** @var ilMailGUI */
    private $parentObject;

    /** @var  */
    private $preload_childs;

    /** @var \ILIAS\DI\UIServices */
    private $ui;

    /** @var ilTree */
    private $tree;

    /** @var  */
    private $root_id;

    /** @var array */
    private $open_nodes = array();

    /** @var array */
    private $custom_open_nodes = array();

    private $type_white_list = array();

    private $type_black_list = array();

    /** @var \ilLanguage */
    private $lng;

    /** @var \ilCtrl */
    private $ctrl;

    /** @var \Psr\Http\Message\ServerRequestInterface */
    private $httpRequest;

    private $order_field = "";

    private $search_term = "";

    private $childs = array();

    private $all_childs = array();

    private $preloaded = false;

    private $order_field_numeric = false;


    /**
     * ilMailExplorer constructor.
     * @param $a_parent_obj
     * @param $a_parent_cmd
     * @param $a_user_id
     */
    public function __construct($a_parent_obj, $a_parent_cmd, $a_user_id)
    {
        global $DIC;

        $this->lng          = $DIC->language();
        $this->ctrl         = $DIC->ctrl();
        $this->httpRequest  = $DIC->http()->request();
        $this->parentObject = $a_parent_obj;
        $this->ui           = $DIC->ui();

        $this->tree = new ilTree($a_user_id);
        $this->tree->setTableNames('mail_tree', 'mail_obj_data');
    }

    function getNodeContent($a_node)
    {
        $content = $a_node['title'];

        if ($a_node['child'] == $this->getNodeId($this->getRootNode())) {
            $content = $this->lng->txt('mail_folders');
        } elseif ($a_node['depth'] < 3) {
            $content = $this->lng->txt('mail_' . $a_node['title']);
        }

        return $content;
    }

    function getNodeIcon($a_node)
    {
        if ($a_node['child'] == $this->getNodeId($this->getRootNode())) {
            $icon = ilUtil::getImagePath('icon_mail.svg');
        } else {
            $iconType = $a_node['m_type'];
            if ($a_node['m_type'] === 'user_folder') {
                $iconType = 'local';
            }

            $icon = ilUtil::getImagePath('icon_' . $iconType . '.svg');
        }

        return $icon;
    }

    function getNodeIconAlt($a_node)
    {
        $text = $this->lng->txt('icon') . ' ' . $this->lng->txt($a_node['m_type']);

        if ($a_node['child'] == $this->getNodeId($this->getRootNode())) {
            $text = $this->lng->txt('icon') . ' ' . $this->lng->txt('mail_folders');
        }

        return $text;
    }

    function getNodeHref($a_node)
    {
        if ($a_node['child'] == $this->getNodeId($this->getRootNode())) {
            $a_node['child'] = 0;
        }

        $this->ctrl->setParameterByClass('ilMailFolderGUI', 'mobj_id', $a_node['child']);
        $href = $this->ctrl->getLinkTargetByClass('ilMailFolderGUI', '', '', false, false);
        $this->ctrl->clearParametersByClass('ilMailFolderGUI');

        return $href;
    }

    function isNodeHighlighted($a_node)
    {
        $folderId = (int) ($this->httpRequest->getQueryParams()['mobj_id'] ?? 0);

        if (
            $a_node['child'] == $folderId ||
            (0 === $folderId && $a_node['child'] == $this->getNodeId($this->getRootNode()))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Get Tree UI
     *
     * @return \ILIAS\UI\Component\Tree\Tree|object
     */
    public function getTreeComponent()
    {
        $f = $this->ui->factory();
        /** @var ilTree $tree */
        $tree = $this->tree;

        $subtree  = $tree->getChilds($tree->readRootId());
        $data     = $subtree;

        $tree = $f->tree()
                  ->expandable($this)
                  ->withData($data)
                  ->withHighlightOnNodeClick(true);

        return $tree;
    }

    /**
     * Get a list of records (that list can also be empty).
     * Each record will be relayed to $this->build to retrieve a Node.
     * Also, each record will be asked for Sub-Nodes using this function.
     * @return array
     */
    public function getChildren($record, $environment = null) : array
    {
        return $this->getChildsOfNode($record["child"]);
    }

    /**
     * Build and return a Node.
     * The renderer will provide the $factory-parameter which is the UI-factory
     * for nodes, as well as the (unspecified) $environment as configured at the Tree.
     * $record is the data the node should be build for.
     * @return \ILIAS\UI\Component\Tree\Node
     */
    public function build(
        \ILIAS\UI\Component\Tree\Node\Factory $factory,
        $record,
        $environment = null
    ) : \ILIAS\UI\Component\Tree\Node\Node {
        $node = $this->createNode($factory, $record);

        $href = $this->getNodeHref($record);

        if ($href)
        {
            $node = $node->withAdditionalOnLoadCode( function($id) use ($href) {
                $js = "$('#$id').find('.node-label').on('click', function(event) {
                            window.location = '{$href}';
                            return false;
                        });";
                return $js;
            });
        }

        if ($this->isNodeOpen($record["child"]))
        {
            $node = $node->withExpanded(true);
        }
        //$node = $node->withHighlighted(true);

        return $node;
    }

    /**
     * Get HTML
     *
     * @return string html
     */
    public function getHTML()
    {
        if ($this->getPreloadChilds()) {
            $this->preloadChilds();
        }

        return $this->render();
    }

    /**
     * Handle explorer internal command.
     *
     * @return boolean true, if an internal command has been performed.
     */
    public function handleCommand()
    {
        if ($_GET["exp_cmd"] != "" &&
            $_GET["exp_cont"] == $this->getContainerId()
        ) {
            $cmd = $_GET["exp_cmd"];
            if (in_array($cmd, array("openNode", "closeNode", "getNodeAsync"))) {
                $this->$cmd();
            }

            return true;
        }
        return false;
    }

    /**
     * @param $factory
     * @param $node
     * @return mixed
     */
    private function createNode(
        \ILIAS\UI\Component\Tree\Node\Factory $factory,
        $node
    ) {
        global $DIC;

        $path = $this->getNodeIcon($node);

        $icon = $DIC->ui()
                    ->factory()
                    ->symbol()
                    ->icon()
                    ->custom($path, 'a');

        $simple = $factory->simple($this->getNodeContent($node), $icon);

        return $simple;
    }


    /**
     * Get childs of node
     *
     * @param int $a_parent_node_id parent id
     * @return array childs
     */
    private function getChildsOfNode($a_parent_node_id)
    {
        if ($this->preloaded && $this->getSearchTerm() == "") {
            if (is_array($this->childs[$a_parent_node_id])) {
                return $this->childs[$a_parent_node_id];
            }
            return array();
        }

        $wl = $this->getTypeWhiteList();
        if (is_array($wl) && count($wl) > 0) {
            $childs = $this->tree->getChildsByTypeFilter($a_parent_node_id, $wl, $this->getOrderField());
        }
        else {
            $childs = $this->tree->getChilds($a_parent_node_id, $this->getOrderField());
        }

        // apply black list filter
        $blacklist = $this->getTypeBlackList();
        if (is_array($blacklist) && count($blacklist) > 0) {
            $blacklistChildren = array();
            foreach($childs as $key => $child) {
                if (!in_array($child["type"], $blacklist) && $this->matches($child)) {
                    $blacklistChildren[$key] = $child;
                }
            }
            return $blacklistChildren;
        }

        $finalChildren = [];
        foreach($childs as $key => $child) {
            if ($this->matches($child)) {
                $finalChildren[$key] = $child;
            }
        }

        return $finalChildren;
    }

    /**
     * Get all open nodes
     *
     * @param
     * @return
     */
    private function isNodeOpen($node_id)
    {
        return ($this->getNodeId($this->getRootNode()) == $node_id
            || in_array($node_id, $this->open_nodes)
            || in_array($node_id, $this->custom_open_nodes));
    }

    /**
     * Get preload childs
     *
     * @return boolean preload childs
     */
    private function getPreloadChilds()
    {
        return $this->preload_childs;
    }

    /**
     * Preload childs
     */
    private function preloadChilds()
    {
        $subtree = $this->tree->getSubTree($this->getRootNode());
        foreach ($subtree as $s) {
            $wl = $this->getTypeWhiteList();
            if (is_array($wl) && count($wl) > 0 && !in_array($s["type"], $wl)) {
                continue;
            }
            $bl = $this->getTypeBlackList();
            if (is_array($bl) && count($bl) > 0 && in_array($s["type"], $bl)) {
                continue;
            }
            $this->childs[$s["parent"]][] = $s;
            $this->all_childs[$s["child"]] = $s;
        }

        if ($this->order_field != "") {
            foreach ($this->childs as $k => $childs) {
                $this->childs[$k] = ilUtil::sortArray(
                    $childs,
                    $this->order_field,
                    "asc",
                    $this->order_field_numeric
                );
            }
        }

        // sort childs and store prev/next reference
        if ($this->order_field == "") {
            $this->all_childs = ilUtil::sortArray(
                $this->all_childs,
                "lft",
                "asc",
                true,
                true
            );

            $prev = false;
            foreach ($this->all_childs as $k => $c) {
                if ($prev) {
                    $this->all_childs[$prev]["next_node_id"] = $k;
                }
                $this->all_childs[$k]["prev_node_id"] = $prev;
                $this->all_childs[$k]["next_node_id"] = false;
                $prev = $k;
            }
        }

        $this->preloaded = true;
    }

    /**
     * Render tree
     *
     * @return string
     */
    private function render()
    {
        $r = $this->ui->renderer();

        return $r->render([
            $this->getTreeComponent()
        ]);
    }

    /**
     * Get id for node
     *
     * @param mixed $a_node node object/array
     * @return string id
     */
    private function getNodeId($a_node)
    {
        return $a_node["child"];
    }

    /**
     * Get root node
     *
     * @return mixed node object/array
     */
    function getRootNode()
    {
        if (!isset($this->root_node_data)) {
            $this->root_node_data =  $this->tree->getNodeData($this->getRootId());
        }
        return $this->root_node_data;
    }


    private function getRootId()
    {
        return $this->root_id
            ? $this->root_id
            : $this->tree->readRootId();
    }

    /**
     * Get type white list
     *
     * @return array array of strings of node types that should be retrieved
     */
    private function getTypeWhiteList()
    {
        return $this->type_white_list;
    }

    private function getOrderField()
    {
        return $this->order_field;
    }

    private function getTypeBlackList()
    {
        return $this->type_black_list;
    }

    /**
     * Does a node match a search term (or is search term empty)
     *
     * @param array
     * @return bool
     */
    private function matches($node): bool
    {
        if ($this->getSearchTerm() == "" ||
            is_int(stripos($this->getNodeContent($node), $this->getSearchTerm()))
        ) {
            return true;
        }
        return false;
    }

    private function getSearchTerm()
    {
        return $this->search_term;
    }
}
