<?php
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class pageUrls extends AbstractHelper
{
    /**
     * Récupère les url utiles pour une page
     *
     * @param array   $props    les propriétés utiles
     * @param o:item  $item     la ressource
     * @param string  $type     le type de ressource
     * 
     * @return array
     */
    public function __invoke($props, $item, $type)
    {
        $view = $this->getView();
        $urls = [];
        //paramètre l'url de l'autocompletion
        $urls["autoComplete"] = $view->basePath()."/api/items?";
        if($type=='concept'){
            //requête pour récupèrer les positions sémantique sonar de cet item
            $urls["urlDataSonar"] = '../../../api/items?resource_template_id='.$props['rtSonar']->id()
                .'&property[0][joiner]=and&property[0][property]='.$props['oa:hasSource']->id().'&property[0][type]=res&property[0][text]='.$item->id()
                .'&property[1][joiner]=and&property[1][property]='.$props['ma:hasRatingSystem']->id().'&property[1][type]=res&property[1][text]=';
            $urls["autoComplete"] .= "resource_classes_id=".$props['skos:Concept']->id()
                ."&property[0][joiner]=and&property[0][property]=".$props['skos:prefLabel']->id();
            $urls["autoCompleteRedir"] = "explorer-concept?id=";
        }
        if($type=='argumentation'){
            //récupère les item de class plmk:CarteExpression
            $urls["autoComplete"] .= "resource_class_id=".$props['plmk:CarteExpression']->id()
                . "&property[0][joiner]=and&property[0][property]=".$props['dcterms:title']->id();
        }
        if($type=='reseau'){
            $urls["getDataReseau"] = $props['site']->siteUrl()."/page/ajax?json=1&type=conceptsMonde";
        }
        if($type=='arbre'){
            $urls["getDataArbre"] = $props['site']->siteUrl()."/page/ajax?json=1&type=conceptsMonde&format=tree&nivMax=2";
        }
        if($type=='rapports-mondes'){
            $urls["aleaPhotoMondes"] = $props['site']->siteUrl()."/page/ajax?json=1&type=aleaPhotoMondes";
            $urls["ajoutRapport"] = $props['site']->siteUrl()."/page/ajax?json=1&type=ajoutRapport";            
        }        
        $urls["autoComplete"] .= "&sort_by=title"
                ."&property[0][type]=in&property[0][text]=";        

        return $urls;
    }
}