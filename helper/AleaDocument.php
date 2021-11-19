<?php
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class AleaDocument extends AbstractHelper
{
    /**
     * Select a random list of concept.
     *
     * @param string    $nb        Nombre de document
     * @param array     $itemSet   collection des documents
     * @return array
     */
    public function __invoke($nb, $itemSet)
    {
        $view = $this->getView();
        return $view->api()->search('items',['item_set_id'=>$itemSet,
            'limit' => $nb,
            'sort_by'=>'random'
        ])->getContent();

    }
}
