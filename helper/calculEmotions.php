<?php
namespace OmekaTheme\Helper;

use Zend\View\Helper\AbstractHelper;

class calculEmotions extends AbstractHelper
{
    /**
     * Calcule les emotions à partir des positions sémantiques
     *
     * @param int    $id    identifiant de l'action
     * 
     * @return array
     */
    public function __invoke($id)
    {
        $api = $this->getView()->api();
        $props['schema:actionApplication'] = $api->search('properties', ['term' => 'schema:actionApplication'])->getContent()[0];

        //récupère les position de l'actions
        $param = array();
        $param['property'][0]['property']= $props['schema:actionApplication']->id()."";
        $param['property'][0]['type']='res';
        $param['property'][0]['text']=$id; 
        $posis = $api->search('items',$param)->getContent();
        //calcul les positions pour chaque étape
        $calculs = ['docs'=>[],'cpts'=>[],'details'=>[],'actants'=>[]];
        $doublons = ['docs'=>[],'cpts'=>[],'actants'=>[]];
        foreach ($posis as $p) {
            $t = 1;
            $cpt = $p->value('jdc:hasConcept',['all' => true]);
            $rate = $p->value('ma:hasRating',['all' => true]);
            $doc = $p->value('jdc:hasDoc',['all' => true]);
            $actant = $p->value('ma:hasCreator')->valueResource();
            $date = $p->value('jdc:creationDate');
            $nbDoc = count($doc);
            $nbCpt = count($cpt);

            if(!isset($doublons['actants'][$actant->id()])){
                $doublons['actants'][$actant->id()]=count($calculs['actants']);
                $calculs['actants'][]=['o:id'=>$actant->id(),'o:title'=>$actant->displayTitle(),'vals'=>0];
            }

            //le premier document correspond au media le deuxième à l'item
            for ($j=0; $j < $nbDoc; $j++) { 
                for ($i=0; $i < $nbCpt; $i++) {
                    $rD = $doc[$j]->valueResource(); 
                    $rC = $cpt[$i]->valueResource(); 
                    $d = [
                        'doc'=> $rD->id()
                        ,'cpt'=>$rC->id()
                        ,'val'=>$rate[$i]->value()
                    ];
                    //regroupe les infos pour les afficher dans emotionGeneva.js
                    if(!isset($doublons['docs'][$d['doc']])){
                        $doublons['docs'][$d['doc']]=count($calculs['docs']);
                        if($rD->resourceName()=='media')
                            $medias = [$rD];
                        else
                            $medias = $rD->media();

                        $calculs['docs'][]=['o:title'=>$rD->displayTitle()
                            ,'o:id'=>$d['doc']
                            ,'medias'=>$medias
                            ,'vals'=>$d['val']];
                    }else $calculs['docs'][$doublons['docs'][$d['doc']]]['vals'] += $d['val'];


                    if(!isset($doublons['cpts'][$d['cpt']])){
                        $doublons['cpts'][$d['cpt']] = count($calculs['cpts']);
                        $calculs['cpts'][]=['o:id'=>$d['cpt'],'o:title'=>$rC->displayTitle(),'vals'=>$d['val']];
                    }
                    else $calculs['cpts'][$doublons['cpts'][$d['cpt']]]['vals'] += $d['val'];
                    
                    $calculs['actants'][$doublons['actants'][$actant->id()]]['vals'] += $d['val'];

                    //enregistre le détails
                    $d['date']=$date->value();
                    $d['actant']=$actant->id();
                    $calculs['details'][] = $d;
                }
            }
        }
        //regroupe les données
        return $calculs;

    }

}
