<?php
namespace OmekaTheme\Helper;

use Zend\View\Helper\AbstractHelper;

class resultEmotions extends AbstractHelper
{
    /**
     * liste des concepts indexé par le nom
     *
     * @var concepts
     */
    protected $concepts = [];
    protected $api;
    protected $emos=[];
    protected $doublons=[];

    /**
     * Calcule les emotions à partir des positions sémantiques
     *
     * @param int    $action    identifiant de l'action
     * @param int    $crible    identifiant du crible des émotions
     * 
     * @return array
     */
    public function __invoke($action, $crible)
    {
        $this->api = $this->getView()->api();
        $oCribleEmo = $this->api->read('items', $crible)->getContent();
        $cribleEmo = $this->getView()->CribleFactory("",$oCribleEmo);

        //récupère l'identifiant des propriétés
        $idsP = [
            'actionApplication'=>$this->api->search('properties', ['term' => 'schema:actionApplication'])->getContent()[0]->id()            
            ,'hasRatingSystem'=>$this->api->search('properties', ['term' => 'ma:hasRatingSystem'])->getContent()[0]->id()            
            ,'title'=>$this->api->search('properties', ['term' => 'dcterms:title'])->getContent()[0]->id()            
            ,'distanceCenter'=>$this->api->search('properties', ['term' => 'jdc:distanceCenter'])->getContent()[0]->id()            
            ,'distanceConcept'=>$this->api->search('properties', ['term' => 'jdc:distanceConcept'])->getContent()[0]->id()                        
            ,'hasConcept'=>$this->api->search('properties', ['term' => 'jdc:hasConcept'])->getContent()[0]->id()            
            ,'hasActant'=>$this->api->search('properties', ['term' => 'jdc:hasActant'])->getContent()[0]->id()          
            ,'hasDoc'=>$this->api->search('properties', ['term' => 'jdc:hasDoc'])->getContent()[0]->id()      
            ,'hasRating'=>$this->api->search('properties', ['term' => 'ma:hasRating'])->getContent()[0]->id()            
            ,'rtPosi'=>$this->api->search('resource_templates', ['label' => 'Position sémantique : Geneva Emotion'])->getContent()[0]->id()            
            ,'rtPosiCor'=>$this->api->search('resource_templates', ['label' => 'Position sémantique : Geneva Emotion corrections'])->getContent()[0]->id()                        
        ]; 


        //récupère les Positions sémantiques : Geneva Emotion
        $params = [
            $idsP['actionApplication']          
            ,$idsP['hasRatingSystem']          
            ,$idsP['title']          
            ,$idsP['distanceCenter']
            ,$idsP['hasActant']      
            ,$idsP['hasDoc']        
            ,"%Item%"
            ,$action     
            ,$idsP['rtPosi']           
        ]; 
        $process = $this->getView()->CribleFactory("",$oCribleEmo,'getProcessCribleValue',$params);    
        //construction des emotions
        $this->getEmoByProcess($process, $cribleEmo);

        //récupère les corrections des Positions sémantiques : Geneva Emotion
        $params = [
            $idsP['actionApplication']          
            ,$idsP['hasRatingSystem']          
            ,$idsP['title']          
            ,$idsP['hasConcept']
            ,$idsP['title']                           
            ,$idsP['hasRating']       
            ,$idsP['hasActant']      
            ,$idsP['hasDoc']        
            ,$idsP['distanceConcept']        
            ,$idsP['title']                           
            ,$idsP['rtPosiCor']
        ];     
        $cor = $this->getView()->CribleFactory("",$oCribleEmo,'getCorCribleValue',$params);    
        //construction des emotions
        $this->getEmoByCor($cor, $cribleEmo);

        //construction du tableau de réponse
        $result = [];
        foreach ($this->emos as $e) {
            $e['total']=$e['Importance']+$e['Evaluations']+$e['Corrections']+$e['Documents']+$e['Actants'];
            $result[]=$e;
        }

        return $result;

    }

    /**
     * Calcule les emotions à partir des processus
     *
     * @param array    process      liste des processus
     * @param array    cribleEmo    crible des émotions geneva
     * 
     * 
     * @return array
     */
    function getEmoByProcess($process, $cribleEmo){
        $nbConcept = count($cribleEmo['concepts']);
        foreach ($process as $p) {
            $valence = isset($p['Valence']) ? $p['Valence'] : 0; 
            $controle = isset($p['Contrôle']) ? $p['Contrôle'] : 0; 
            $degre = ((atan2($valence-50, $controle-50)* (180 / pi())) + 360) % 360;
            //transforme les degrées en n° d'émotion
            $numEmo = round($this->scaleData($degre, 0, 360, 0, $nbConcept-1));
            $id = $cribleEmo['concepts'][$numEmo]->id();
            if(!isset($this->emos[$id])){
                $ico=$cribleEmo['concepts'][$numEmo]->value('plmk:hasIcon')->asHtml();
                $this->emos[$id]=['numEmo'=>$numEmo,'idEmo'=>$id,'titreEmo'=>$cribleEmo['concepts'][$numEmo]->displayTitle()
                    ,'ico'=>$ico,'process'=>[],'cor'=>[]
                    ,'Importance'=>0,'Evaluations'=>0,'Corrections'=>0,'Documents'=>0,'Actants'=>0];
            }
            $this->emos[$id]['Importance']+=1;        
            $this->emos[$id]['Evaluations']+=1;        
            $this->emos[$id]['process'][]=$p;
            //calcul le nombre d'élément par facette
            foreach ($p['docs'] as $d) {
                if(!isset($this->doublons[$id.'_doc_'.$d])){
                    $this->emos[$id]['Documents']+=1; 
                    $this->doublons[$id.'_doc_'.$d]=1;
                }       
            }
            foreach ($p['actants'] as $d) {
                if(!isset($this->doublons[$id.'_actant_'.$d])){
                    $this->emos[$id]['Actants']+=1; 
                    $this->doublons[$id.'_actant_'.$d]=1;
                }       
            }
        }
        return $this->emos;

    }

    /**
     * Calcule les emotions à partir des corrections
     *
     * @param array    cor        liste des corrections
     * @param array    cribleEmo    crible des émotions geneva
     * 
     * 
     * @return array
     */
    function getEmoByCor($cor, $cribleEmo){
        foreach ($cor as $c) {
            if(!isset($this->emos[$c['cptId']])){
                //récupère l'icone
                $ico="";
                foreach ($cribleEmo['concepts'] as $ce) {
                    if($ce->id()==$c['cptId'])$ico=$ce->value('plmk:hasIcon')->asHtml();
                }
                $this->emos[$c['cptId']]=['idEmo'=>$c['cptId'],'titreEmo'=>$c['cpt']
                ,'ico'=>$ico,'process'=>[],'cor'=>[]
                ,'Importance'=>0,'Evaluations'=>0,'Corrections'=>0,'Documents'=>0,'Actants'=>0];
            }
            $this->emos[$c['cptId']]['Importance']+=$c['cptVal'];
            $this->emos[$c['cptId']]['cor'][]=$c;
            $this->emos[$c['cptId']]['Corrections']+=1;        
            //calcul le nombre d'élément par facette
            if(!isset($this->doublons[$c['cptId'].'_doc_'.$c['docs']])){
                $this->emos[$c['cptId']]['Documents']+=1; 
                $this->doublons[$c['cptId'].'_doc_'.$c['docs']]=1;
            }       
            if(!isset($this->doublons[$c['cptId'].'_actant_'.$c['actants']])){
                $this->emos[$c['cptId']]['Actants']+=1; 
                $this->doublons[$c['cptId'].'_actant_'.$c['actants']]=1;
            }       
        }
        return $this->emos;

    }


    function scaleData($value, $srcmin, $srcmax, $destmin, $destmax){
        
        //how far in the source range is $x (0..1)
        $pos = (($value - $srcmin) / ($srcmax-$srcmin));         
        //figure out where that puts us in the destination range
        $rescaled = ($pos * ($destmax-$destmin)) + $destmin;

        return $rescaled;
    }

}
