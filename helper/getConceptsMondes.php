<?php
namespace OmekaTheme\Helper;

use Zend\View\Helper\AbstractHelper;

class getConceptsMondes extends AbstractHelper
{
    var $rs;
    var $doublons;
    var $nivMax=1;//profondeur de la recherche
    var $api;
    var $reseau;
    var $item;
    var $cols = ['num','id'];
    var $lignes = [];
    var $treeData;
    //pour éviter un réseau trop grand on exclut des relations
    var $excluRela = ['skos:semanticRelation','cito:isCompiledBy'];

    /**
     * Récupère les propriétés utiles pour une page
     * 
     * @param int                   $nivMax     pronfondeur du reseau
     * @param string                $format     défini le format de retour
     * 
     * @return array
     */
    public function __invoke($nivMax=0, $format='reseau')
    {
        $this->api = $this->getView()->api();
        if($nivMax)$this->nivMax=$nivMax;
        
        $this->reseau = ['nodes'=>[],'links'=>[]];

        //récupère l'identifiant de la collection
        $idItemSet = $this->getView()->themeSetting('item_set_id_monde');
        //récupère la collection
        $monde = $this->api->read('item_sets',$idItemSet)->getContent();
        
        //construction d'une structure en réseeaux : nodes + links
        if($format == 'reseau'){
          $rs = $this->getReseau($monde);
        }

          //construction sous forme de tableur
        //TODO:finir si module bulckExport pas suffisant
        if($format == 'csv'){
          $rs = $this->getCsv($monde->id());
        }


        //construction sous forme d'arbre
        //TODO:finir si module bulckExport pas suffisant
        if($format == 'tree'){
          $rs = $this->getTree($monde);
        }

        return $rs;

    }

  	/**
     * construction de l'arbre
     *
     * @param  oItem    $item
     * @param  int      $niv
     *
     * @return array
     */
    function getTree($item, $niv=0){

      /*construction de la réponse pour un affichage en arbre
      {
        "name": "Top Level",
        "children": [
          { 
        "name": "Level 2: A",
            "children": [
              { "name": "Son of A" },
              { "name": "Daughter of A" }
            ]
          },
          { "name": "Level 2: B" }
        ]
      };
      */
      $tree = ["name"=>$item->displayTitle(), "id"=>$item->id(), "type"=>"item", "children"=>[]];

      //ajoute les liens de l'item
      $relations = $item->subjectValues();
      foreach ($relations as $k => $r) {
          //ne prend pas en compte les items
          if($k=='item'){
              foreach ($r as $v) {
                  $vr = $v->resource();
                  if($niv < $this->nivMax)
                    $tree["children"][]=$this->getTree($vr, $niv+1);
              }        
          }
      }
      //vérifie si la ressource est un itemset
      if($item->resourceName()=='item_sets'){
        //récupère les items
        $items = $this->api->search('items',['item_set_id'=>$item->id()])->getContent();
        foreach ($items as $i) {
          if($niv < $this->nivMax)
            $tree["children"][]=$this->getTree($i, $niv+1);
        }
      }

      //ajoute les ressources de l'item
      $relations = $item->values();
      foreach ($relations as $k => $r) {
        $i=0;
        $treeProp=false;
        foreach ($r['values'] as $v) {
          if($v->type()=='resource'){
            if($i==0){
              //ajoute la propriété comme branche de l'arbre             
              $treeProp = ["name"=>$v->property()->label(), "id"=>$v->property()->id(), "type"=>"property", "children"=>[]];
            }
            $vr = $v->valueResource();
            if($niv < $this->nivMax && !in_array($k, $this->excluRela))
              $treeProp["children"][]=$this->getTree($vr, $niv+1);
            $i++;
          }
          if($treeProp)$tree["children"][]=$treeProp;
        }
      }
      //ajoute les target de l'annotation
      if($item->resourceClass() && $item->resourceClass()->label()=="Annotation"){
        $tgts = $item->targets();
        foreach ($tgts as $t) {
          $vals = $t->value('rdf:value',['all'=>true]);
          foreach ($vals as $v) {
            $rv = $v->valueResource();
            if($rv){
              $tree["children"][]=$this->getTree($rv, $niv+1);
            }
          }
        }  
      }

      return $tree;      

    }


  	/**
     * construction du reseau
     *
     * @param  array   $item
     *
     * @return array
     */
    function getReseau($item){

      $this->addDocument($item,0);

      return $this->reseau;      

    }

	/**
     * construction du csv
     *
     * @param  int   $itemSetId
     *
     * @return array
     */
    function getCsv($itemSetId){

      //récupère les items de la collection
      $items = $this->api->search('items',['item_set_id'=>$itemSetId],['limit'=>0])->getContent();

      //construction d'une structure en tableur
      $curligne=0;
      foreach ($items as $item) {
        if($curligne==0){
          $this->getCols($item);
          $this->lignes[0]=$this->cols;
          $curligne++;
        }
        $curligne = $this->getLigne($item,$curligne);
      }

      return $this->lignes;      

    }

	/**
     * récupère les propriétés d'un item
     *
     * @param  o:item   $item
     *
     * @return int
     */
    function getCols($item){
        //liste des propriétés
        $vals = $item->values();
        foreach ($vals as $k => $v) {
          if (!in_array($k, $this->cols)) {
            $this->cols[]=$k;
          }          
        }
    }

  	/**
     * récupère les propriétés d'un item
     *
     * @param  o:item   $item
     *
     * @return int
     */
    function getLigne($item, $numLigne){
        //liste des valeurs
        $vals = $item->values();
        $numCols = 2;
        $nbCols = count($vals)+2;
        $maxLigne = $numLigne;
        $this->lignes[$numLigne][0] = $numLigne;
        $this->lignes[$numLigne][1] = $item->id();
        foreach ($vals as $k => $v) {
          $curLigne=$numLigne;
          $val = $item->value($k, ['all' => true]);
          foreach ($val as $value) {
            $this->lignes[$curLigne][0] = $curLigne;
            //complète la ligne
            for ($curCol=1; $curCol < $numCols; $curCol++) { 
              if(!isset($this->lignes[$curLigne][$curCol]))
                $this->lignes[$curLigne][$curCol]="";
            }
            //ajoute la valeur de la colonne
            $relatedResource = $value->valueResource();
            if ($relatedResource) {
              $this->lignes[$curLigne][$numCols]=$relatedResource->id().' - '.$relatedResource->displayTitle();
            }else{
              $this->lignes[$curLigne][$numCols]=$value->__toString();
            }
            $curLigne++;
          }
          $maxLigne = $maxLigne < $curLigne ? $curLigne : $maxLigne;
          $numCols++;
        }
        return $maxLigne;
    }

	/**
     * Ajout un document au reseau
     *
     * @param  o:item   $item
     * @param  int      $niv
     *
     * @return int
     */
    function addDocument($item, $niv){

        /*construction de la réponse pour un affichage réseau
          {
            "nodes": [
                {
                "id": "Myriel",
                "group": 1,
                "size" : 10
                },
                {
                "id": "Napoleon",
                "group": 1
                },
            ],
            "links": [
                {
                "source": "Napoleon",
                "target": "Myriel",
                "value": 1
                },
                {
                "source": "Mlle.Baptistine",
                "target": "Myriel",
                "value": 8
                },
            ]
          };
         */
        //ajoute l'item
        if(!$this->addNoeud($item)) return;
  
        //ajoute les liens de l'item
        $relations = $item->subjectValues();
        foreach ($relations as $k => $r) {
            //ne prend pas en compte les items
            if($k=='item'){
                foreach ($r as $v) {
                    $vr = $v->resource();
                    $this->reseau['links'][] = ["target"=>$vr->id(),"source"=>$item->id(), "value"=>1, "group"=>$k];
                    if($niv < $this->nivMax)
                      $this->addDocument($vr, $niv+1);
                    else{
                      $this->addNoeud($vr);
                    }
                }        
            }
        }
        //vérifie si la ressource est un itemset
        if($item->resourceName()=='item_sets'){
          //récupère les items
          $items = $this->api->search('items',['item_set_id'=>$item->id()])->getContent();
          foreach ($items as $i) {
            $this->reseau['links'][] = ["target"=>$i->id(),"source"=>$item->id(), "value"=>1, "group"=>'Collection'];
            if($niv < $this->nivMax)
              $this->addDocument($i, $niv+1);
            else{
              $this->addNoeud($i);
            }
          }
        }


        //ajoute les ressources de l'item
        $relations = $item->values();
        foreach ($relations as $k => $r) {
          foreach ($r['values'] as $v) {
            if($v->type()=='resource'){
              $vr = $v->valueResource();
              //les groupes sont des noeuds
              $oProp = $this->addPropNoeud($k);
              $this->reseau['links'][] = ["target"=>$oProp->id(),"source"=>$item->id(), "value"=>1, "group"=>$k];  
              $this->reseau['links'][] = ["target"=>$vr->id(),"source"=>$oProp->id(), "value"=>1, "group"=>$k];  
              if($niv < $this->nivMax && !in_array($k, $this->excluRela))
                $this->addDocument($vr, $niv+1);
              else{
                $this->addNoeud($vr);
              }
            }
          }
        }
        //ajoute les target de l'annotation
        if($item->resourceClass() && $item->resourceClass()->label()=="Annotation"){
          $tgts = $item->targets();
          foreach ($tgts as $t) {
            $vals = $t->value('rdf:value',['all'=>true]);
            foreach ($vals as $v) {
              $rv = $v->valueResource();
              if($rv){
                $this->addNoeud($rv);            
                $this->reseau['links'][] = ["target"=>$rv->id(),"source"=>$item->id(), "value"=>1, "group"=>$item->value('oa:motivatedBy')->asHtml()];  
              }
            }
          }  
        }
  
  
      }
  
      /**
       * Ajout un noeud dans le réseau
       *
       * @param  o:item   $n
       *
       * @return boolean
       */
      function addNoeud($n){
        if(!isset($this->doublons[$n->id()])){
          if(get_class($n)=="Omeka\Api\Representation\PropertyRepresentation"){
            $this->reseau['nodes'][] = ["id"=>$n->id(),"size"=>1,"group"=>"property","title"=>$n->term()];  
          }else{
            $group = $n->resourceClass() ? $n->resourceClass()->label() : "item";
            $this->reseau['nodes'][] = ["id"=>$n->id(),"size"=>1,"group"=>$group,"title"=>$n->displayTitle()];  
            $this->doublons[$n->id()]=count($this->reseau['nodes'])-1;  
          }
          return true;
        }else{
          $this->reseau['nodes'][$this->doublons[$n->id()]]["size"]++;      
          return false;
        }
      }

      /**
       * Ajoute la propriété comme noeud
       *
       * @param  string  $prop
       *
       * @return ressource
       */
      function addPropNoeud($prop){
        //récupère la propriétés
        if(!isset($this->doublons[$prop])){
          $this->doublons[$prop] = $this->api->search('properties', ['term' => $prop])->getContent()[0];   
          $this->addNoeud($this->doublons[$prop]);
        } 
        return $this->doublons[$prop];
      }

}