<?php
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class getItem extends AbstractHelper
{
    /**
     * Récupère les propriétés utiles pour une page
     * 
     * @param string    $type
     *
     * @return o:item
     */
    public function __invoke($type)
    {
        $view = $this->getView();
        $item = false;
        if($type=="concept"){
                $idConcept =$view->params()->fromQuery('id', '');
                $concept = $view->params()->fromQuery('concept', '');
                if($idConcept){
                        $item = $view->api()->read('items', $idConcept)->getContent();
                }elseif($concept){
                        $props['skos:prefLabel'] = $view->api()->search('properties', ['term' => 'skos:prefLabel'])->getContent()[0];
                        $param = array();                                
                        $param['property'][0]['property']= $props['skos:prefLabel']->id()."";
                        $param['property'][0]['type']='eq';
                        $param['property'][0]['text']=$concept; 
                        $result = $view->api()->search('items',$param)->getContent();
                        if(count($result))
                                $item = $result[0];
                }else{
                        $aC = $view->AleaConcept(1);
                        $item = $aC[0];        
                }        
        }
        if($type=="document"){
                $idDoc =$view->params()->fromQuery('id', '');
                if($idDoc){
                        $item = $view->api()->read('items', $idDoc)->getContent();
                } else{
                        $aC = $view->AleaDocument(1,$itemSet);
                        $item = $aC[0];        
                }        
        }

        return $item;
    }
}