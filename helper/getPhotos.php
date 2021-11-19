<?php
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class getPhotos extends AbstractHelper
{
    /**
     * Récupère les propriétés utiles pour une page
     * 
     * @param string    $q
     * @param integer   $nb
     * @param string    $filtres
     * 
     * @return array
     */
    public function __invoke($q='all', $nb=1, $filtres=[])
    {
        $view = $this->getView();
        $api = $view->api();

        //récupère la liste des photos
        $query = array_merge(['media_type'=>'image/jpeg'], $filtres);
        $result=[];
        $items=[];
        switch ($q) {
            case 'all':
                $items = $api->search('media',$query,['limit'=>0])->getContent();
                break;
            case 'alea':
                $query['limit']=$nb;
                $query['sort_by']='random';
                $items = $api->search('media',$query)->getContent();
                break;
        }

        foreach ($items as $item) {
            $result[]=[
                'id'=>$item->id()
                ,'title'=>$item->displayTitle()
                ,'medias'=>$item->thumbnailUrls()
                ,'source'=>$item->source()
                ,'size'=>$item->size()
            ];
        }

        return $result;
    }

}