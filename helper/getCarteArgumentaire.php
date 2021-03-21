<?php
namespace OmekaTheme\Helper;

use Zend\View\Helper\AbstractHelper;

class getCarteArgumentaire extends AbstractHelper
{
    /**
     * Récupère les propriétés utiles pour une page
     * 
     * @param oItem $oItem
     * @param array $props
     * 
     * @return array
     */
    public function __invoke($oItem, $props)
    {
        $view = $this->getView();
        $api = $view->api();

        $result = [];
        if(!$oItem){
            //récupère toute les carte d'expression        
            $query = [
                'resource_class_id'=>$props['plmk:CarteExpression']->id(),
            ];
            $items = $api->search('items',$query,['limit'=>0])->getContent();
            //construction des résultats
            foreach ($items as $i) {
                $result[] = $this->getCarteInfo($i);
            }
            //
        }else{
            //récupère la définition d'une carte
            $result = $this->getCarteInfo($oItem);
            $geos = $oItem->value('geom:geometry', ['all' => true]);
            foreach ($geos as $geo) {                
                $result=$this->getGeoInfo($geo->valueResource(),$result);
            }

        }

        return $result;
    }

    function getCarteInfo($oItem){
        $dC = $oItem->value('dcterms:created')->asHtml();
        $c = $oItem->value('dcterms:creator') ? $oItem->value('dcterms:creator')->asHtml() : '';
        $w = $oItem->value('ma:frameWidth') ? $oItem->value('ma:frameWidth')->asHtml() : 300;
        $h = $oItem->value('ma:frameHeight') ? $oItem->value('ma:frameHeight')->asHtml() : 300;
        $styles = $oItem->value('oa:styleClass') ? json_decode($oItem->value('oa:styleClass')->__toString()) : "";
        $result = ['label'=>$oItem->displayTitle()." (".$c." - ".$dC.")"
          ,'id'=>$oItem->id()
          ,'title'=>$oItem->displayTitle()
          ,'w'=>$w
          ,'h'=>$h
          ,'urlAdmin'=>$oItem->adminUrl('edit')
          ,'styles'=>$styles
        ];      
        return $result;
    }

    function getGeoInfo($oItem, $result){
        $rc = $oItem->displayResourceClassLabel() ;
        switch ($rc) {
            case 'Ligne':
                $result['links'][] = ['label'=>$oItem->displayTitle()
                        ,'id'=>$oItem->id()
                        ,'src'=>$oItem->value('ma:hasSource')->valueResource()->id()
                        ,'dst'=>$oItem->value('ma:isSourceOf')->valueResource()->id()
                        ,'urlAdmin'=>$oItem->adminUrl('edit')
                        ,'style'=>json_decode($oItem->value('oa:styleClass')->__toString())
                    ];      
                break;
            case 'Envelope':
                $result['nodes'][] = ['label'=>$oItem->value('skos:semanticRelation')->valueResource()->displayTitle()
                        ,'id'=>$oItem->id()
                        ,'idConcept'=>$oItem->value('skos:semanticRelation')->valueResource()->id()
                        ,'x'=>$oItem->value('geom:coordX')->__toString()
                        ,'y'=>$oItem->value('geom:coordY')->__toString()
                        ,'type'=>$oItem->value('dcterms:type')->__toString()
                        ,'urlAdmin'=>$oItem->adminUrl('edit')
                        ,'style'=>json_decode($oItem->value('oa:styleClass')->__toString())
                    ];      
                break;
        }

        return $result;
    }

}