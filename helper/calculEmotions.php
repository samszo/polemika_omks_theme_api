<?php
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class calculEmotions extends AbstractHelper
{
    /**
     * liste des concepts indexé par le nom
     *
     * @var concepts
     */
    protected $concepts = [];
    protected $api;

    /**
     * Calcule les emotions à partir des positions sémantiques
     *
     * @param int    $process    identifiant du processus
     * @param int    $action    identifiant de l'action
     * @return array
     */
    public function __invoke($process, $action)
    {
        $this->api = $this->getView()->api();

        $props['schema:actionApplication'] = $this->api->search('properties', ['term' => 'schema:actionApplication'])->getContent()[0];
        $rtPosi=$this->api->search('resource_templates', ['label' => 'Position sémantique : Geneva Emotion'])->getContent()[0]->id();            

        //récupère les positions de l'actions
        $param = array();
        $param['property'][0]['property']= $props['schema:actionApplication']->id()."";
        $param['property'][0]['type']='res';
        $param['property'][0]['text']=$process; 
        $param['resource_template_id'][]=$rtPosi;

        $posis = $this->api->search('items',$param)->getContent();
        //calcul les positions pour chaque étape
        $calculs = ['docs'=>[],'cpts'=>[],'details'=>[],'actants'=>[],'cribles'=>[]];
        $doublons = ['docs'=>[],'cpts'=>[],'actants'=>[],'cribles'=>[]];
        foreach ($posis as $p) {
            $t = 1;
            $cpt = $p->value('jdc:hasConcept',['all' => true]);
            $rate = $p->value('ma:hasRating',['all' => true]);
            $doc = $p->value('jdc:hasDoc',['all' => true]);            
            $crible = $p->value('ma:hasRatingSystem')->valueResource();
            $actant = $p->value('ma:hasCreator')->valueResource();
            $date = $p->value('jdc:creationDate');
            $nbDoc = count($doc);
            $nbCpt = count($cpt);

            if(!isset($doublons['actants'][$actant->id()])){
                $doublons['actants'][$actant->id()]=count($calculs['actants']);
                $calculs['actants'][]=['o:id'=>$actant->id(),'o:title'=>$actant->displayTitle(),'vals'=>0];
            }

            if(!isset($doublons['cribles'][$crible->id()])){
                $doublons['cribles'][$crible->id()]=count($calculs['cribles']);
                $calculs['cribles'][]=['o:id'=>$crible->id(),'o:title'=>$crible->displayTitle(),'vals'=>0];
            }

            //le premier document correspond au media le deuxième à l'item
            $rM = $doc[0]->valueResource(); 
            $rD = $doc[1]->valueResource(); 
            $d = [
                'doc'=> $rD->id()
                ,'media'=> $rM->id()
            ];
            if(!isset($doublons['docs'][$d['doc']])){
                $doublons['docs'][$d['doc']]=count($calculs['docs']);
                $calculs['docs'][]=['o:title'=>$rD->displayTitle()
                    ,'o:id'=>$d['doc']
                    ,'medias'=>[$rM]
                    ,'vals'=>0
                ];
            }

            for ($i=0; $i < $nbCpt; $i++) {
                $rC = $cpt[$i]->valueResource(); 
                $d['cpt']=$rC->id();
                $d['val']=$rate[$i]->value();

                //calcul les valeurs globales
                $calculs['docs'][$doublons['docs'][$d['doc']]]['vals'] += $d['val'];
                $calculs['actants'][$doublons['actants'][$actant->id()]]['vals'] += $d['val'];
                $calculs['cribles'][$doublons['cribles'][$crible->id()]]['vals'] += $d['val'];

                if(!isset($doublons['cpts'][$d['cpt']])){
                    $doublons['cpts'][$d['cpt']] = count($calculs['cpts']);
                    $calculs['cpts'][]=['o:id'=>$d['cpt'],'o:title'=>$rC->displayTitle(),'vals'=>$d['val']];
                    $this->concepts[$rC->displayTitle()]=$d['cpt'];
                }
                else $calculs['cpts'][$doublons['cpts'][$d['cpt']]]['vals'] += $d['val'];
                
                //enregistre le détails
                $d['date']=$date->value();
                $d['actant']=$actant->id();
                $d['crible']=$crible->id();
                $calculs['details'][] = $d;
            }
        }
        //calcule la position de l'émotion
        //fait en javascript dans emotionGeneva.js

        return $calculs;

    }

}
