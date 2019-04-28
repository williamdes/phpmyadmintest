<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Util;
use PhpMyAdmin\Url;

/**
 * Represents a view node in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class NodeView extends NodeDatabaseChild
{
    /**
     * Initialises the class
     *
     * @param string $name     An identifier for the new node
     * @param int    $type     Type of node, may be one of CONTAINER or OBJECT
     * @param bool   $is_group Whether this object has been created
     *                         while grouping nodes
     */
    public function __construct($name, $type = Node::OBJECT, $is_group = false)
    {
        parent::__construct($name, $type, $is_group);
        $this->icon = Util::getImage('b_props', __('View'));
        $sep = Url::getArgSeparator('html');
        $this->links = array(
            'text' => 'sql.php?server=' . $GLOBALS['server']
                . $sep . 'db=%2$s' . $sep . 'table=%1$s' . $sep . 'pos=0',
            'icon' => 'tbl_structure.php?server=' . $GLOBALS['server']
                . $sep . 'db=%2$s' . $sep . 'table=%1$s',
        );
        $this->classes = 'view';
    }

    /**
     * Returns the type of the item represented by the node.
     *
     * @return string type of the item
     */
    protected function getItemType()
    {
        return 'view';
    }
}
