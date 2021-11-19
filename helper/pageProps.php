<?php
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class pageProps extends AbstractHelper
{
    /**
     * Récupère les propriétés utiles pour une page
     *
     * @return array
     */
    public function __invoke()
    {
        $view = $this->getView();
        $api = $view->api();
        //récupère les propriétés utiles
        $props = [];
        $props['oa:hasSource'] = $api->search('properties', ['term' => 'oa:hasSource'])->getContent()[0];        

        $props['dcterms:title'] = $api->search('properties', ['term' => 'dcterms:title'])->getContent()[0];
        $props['dcterms:description'] = $api->search('properties', ['term' => 'dcterms:description'])->getContent()[0];
        $props['dcterms:type'] = $api->search('properties', ['term' => 'dcterms:type'])->getContent()[0];
        
        $props['plmk:CarteExpression'] = $api->search('resource_classes', ['term' => 'plmk:CarteExpression'])->getContent()[0];
        $props['plmk:hasMonde'] = $api->search('properties', ['term' => 'plmk:hasMonde'])->getContent()[0];        

        return $props;
    }
}