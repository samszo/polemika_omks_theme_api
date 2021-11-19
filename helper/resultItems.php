<?php
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class resultItems extends AbstractHelper
{

    /**
     * Récupère les items correspondant aux résultats de process
     *
     * @param array    $docs   liste des docs
     * 
     * @return array
     */
    public function __invoke($docs)
    {
        $this->api = $this->getView()->api();
        $result = ['items'=>[],'medias'=>[]];
        foreach ($docs as $d) {
            $oItem = $this->api->read('items', $d)->getContent();
            $result['items'][]=$oItem;
            $result['medias']=array_merge($result['medias'], $oItem->media());
        }

        return $result;

    }


}
