<?php
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class getCarteArgumentaire extends AbstractHelper
{
    var $api;
    var $rs;
    var $view;
    var $cartoEditor;

    /**
     * Récupère les propriétés utiles pour une page
     * 
     * @param user  $user
     * @param oItem $oItem
     * @param array $props
     * 
     * @return array
     */
    public function __invoke($user, $oItem, $props)
    {
        $this->view = $this->getView();
        $this->api = $this->view->api();

        $result = [];
        if(!$oItem){
            //récupère toute les carte d'expression        
            $query = [
                'resource_class_id'=>$props['plmk:CarteExpression']->id(),
            ];
            $items = $this->api->search('items',$query,['limit'=>0])->getContent();
            //construction des résultats
            foreach ($items as $i) {
                $this->rs[] = $this->getCarteInfo($i);
            }
            //
        }else{
            //récupère les propriétés
            $this->props = $props;
            
            //récupère la définition d'une carte            
            $this->rs = $this->getCarteInfo($oItem);
            $geos = $oItem->value('geom:geometry', ['all' => true]);
            foreach ($geos as $geo) {                
                $this->getGeoInfo($geo->valueResource());
            }

        }

        return $this->rs;
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

    function getGeoInfo($oItem){
        $rc = $oItem->displayResourceClassLabel() ;
        //récupère l'archétype
        $style = $oItem->value('oa:styleClass')->__toString();
        if(!$oItem->value('jdc:hasArchetype')){
            //recherche si l'archétype existe
            $param = array();
            $param['property'][0]['property']= $this->props['description']->id();
            $param['property'][0]['type']='eq';
            $param['property'][0]['text']=$style;                 
            $arc = $this->api->search('items',$param)->getContent();
            if(count($arc)==0){
                //ajoute l'archétype
                $this->view->CartoEditorFactory(['action'=>'createErchetype','style'=>$style,'item'=>$oItem]);

            }
        }else
            $idArc = $oItem->value('jdc:hasArchetype')->valueResource()->id();

        switch ($rc) {
            case 'Ligne':
                $this->rs['links'][] = ['label'=>$oItem->displayTitle()
                        ,'id'=>$oItem->id()
                        ,'src'=>$oItem->value('ma:hasSource')->valueResource()->id()
                        ,'dst'=>$oItem->value('ma:isSourceOf')->valueResource()->id()
                        ,'urlAdmin'=>$oItem->adminUrl('edit')
                        ,'style'=>json_decode($style)
                    ];      
                break;
            case 'Envelope':
                $this->rs['nodes'][] = ['label'=>$oItem->value('skos:semanticRelation')->valueResource()->displayTitle()
                        ,'id'=>$oItem->id()
                        ,'idConcept'=>$oItem->value('skos:semanticRelation')->valueResource()->id()
                        ,'x'=>$oItem->value('geom:coordX')->__toString()
                        ,'y'=>$oItem->value('geom:coordY')->__toString()
                        ,'type'=>$oItem->value('dcterms:type')->__toString()
                        ,'urlAdmin'=>$oItem->adminUrl('edit')
                        ,'style'=>json_decode($style)
                    ];      
                break;
        }

        return $this->rs;
    }

}