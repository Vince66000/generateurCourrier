<?php

namespace App\Controller;


use Doctrine\DBAL\Driver\PDOConnection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use  \Spipu\Html2Pdf\Html2Pdf;



class convocController  extends  AbstractController {



//  const dbLocal = "aeb_dol";
//  const usrLocal = "aeb.dol";
//  const mdpLocal = 'DmPzTs61NzG4';


    const dbLocal = "dolibarr";
    const usrLocal = "vincent";
    const mdpLocal = 'root';




    /**
     * Affichage premiè_re page de l'appli
     * @return Response
     * @Route("/", name="index")
     */
    public function index() :Response
    {
        return  $this->render('home.html.twig');

    }

    /*****************************************************************************************************************************************************************
     *
     *                                                                      PARTIE CLIENT
     *
     * ***************************************************************************************************************************************************************
     */
    /**
     * Affiche le formulaire de convocs clients
     * @Route("convocationClient", name="convocationClient")
     */
    public function getFormCl() :Response
    {

        return  $this->render('convocations/formConvocClient.html.twig');
    }

    /**
     * Récupèration des infos passées au formulaire et Génération du PDF
     * @Route("generateConvCl", name="generateConvCl")
     */
    public function getConvocClient() {

        $ref =  '%' .$_POST['affaire'] . '%';
        $date = $_POST['datepicker'];
        $heure = $_POST['timepicker'];
        $expert = $_POST['expert'];
        $client = 'Client(e)';

        /**
         * requête qui récupère les coordonées d'un client lié à une affaire particulière
         */
        $db = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getSoc = $db->prepare ('select 
                        llx_projet.ref,
                        llx_socpeople.civility,
                        llx_socpeople.lastname, 
                        llx_socpeople.firstname, 
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.town,
                        llx_projet_extrafields.lieuaff,
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        INNER JOIN llx_projet_extrafields ON llx_projet.rowid = llx_projet_extrafields.fk_object
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160 
                        and llx_c_type_contact.libelle like :client');
        $getSoc->bindParam(':ref', $ref);
        $getSoc->bindParam(':client', $client);

        $getSoc->execute();
        $societe = $getSoc->fetch();
        $refAff = $societe['ref'];

        //L'adresse d'expertise contenant des caractères en trop, je l'explose et exploite seulement les fragments dont j'ai besoin
        $lieuxExp = $societe['lieuaff'];
        $lieuxExp2 = explode(" ", $lieuxExp);
        $count = count($lieuxExp2);
        $lieuxExp3 = '';
        for($i = 4; $i < $count  ; $i++) {
            $lieuxExp3 .=  ' ' . $lieuxExp2[$i] . ' ';

        }


        $template = $this->renderView('convocations/modelConvoc.html.twig', [

            'affaire' => $ref,
            'societe' => $societe,
            'dateRdv' => $date,
            'heureRdv' => $heure,
            'expert' => $expert,
            'lieuxexp' => utf8_encode($lieuxExp3)

        ]);

        $html2pdf = new html2pdf('P', 'A4', 'fr', true, 'utf-8', 10);
        $html2pdf->setTestTdInOnePage(false);
        $html2pdf->writeHTML($template);

        return $html2pdf->output('convocationClt'. $refAff .'.pdf');
    }




    /**
     * ******************************************************************************************************************************************************************************
     *                                     Partie Tiers
     * ******************************************************************************************************************************************************************************
     */
    /**
     * affiche le form qui demande le numÃ©rod'affaire
     * @return Response
     * @Route("convEse1", name="convEse1")
     */
    public function getProjEse() {

        return $this->render('convocations/FormConvEse1.html.twig');
    }


    /**
     * Récupère les contacts liés à l'affaire
     * @return Response
     * @throws \Doctrine\DBAL\Driver\PDOException
     * @Route("getProject" , name="getProject")
     */
    public function getProject() {

        $ref =  '%'. $_POST['refAffaire'] . '%' ;
        $client = 'Client(e)';
        $entreprise = 'Entreprise' . '%';
        $tiers = 'Tiers' . '%';


        $db = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getProj = $db->prepare('select                    
                        llx_socpeople.lastname, 
                        llx_socpeople.firstname, 
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle IN  ("Entreprise", "Entreprise 2", "Entreprise 3", "Tiers", "Tier 2", "Teirs 3")  
                        ');

        $getProj->bindParam(':ref', $ref);
       // $getProj->bindParam(':entreprise', $entreprise);
      //  $getProj->bindParam(':tiers', $tiers);
//        $getProj->bindParam(':client', $client);
        $getProj->execute();

        $res = $getProj->fetchAll();

        return $this->render('convocations/formConvocEse.html.twig', [
            'res' => $res,
            'affaire' => $ref,

        ]);



    }

    /**
     * rÃ©cupÃ©ration des informations et GÃ©nÃ©ration de la convoc Ã  un tiers
     * @Route("generateConvTiers", name="generateConvTiers")
     */
    public function getConvocTiers()
    {

        $affaire = '%' . $_POST['affaire'] . '%';
        $nom = $_POST['nom'];
        $noms = explode(" ", $nom);
//        $prenom = $noms[0];
        $nom = '%' . $noms[1] . '%';
        $date = $_POST['datepicker'];
        $heure = $_POST['timepicker'];
        $expert = $_POST['expert'];
        $dateDesordre = $_POST['dateDesordre'];
        $natureTrav = $_POST['natureTrav'];
        $vref = $_POST['vref'];
        $numReco = $_POST['numReco'];
        $interlocuteur = $_POST['interlocuteur'];
        $dossier = $_POST['dossier'];
        $pj =$_POST['pj'] ;

        $db = new PDOConnection('mysql:host=localhost;dbname=' . self::dbLocal . '', '' . self::usrLocal . '', '' . self::mdpLocal . '');
        $getClient = $db->prepare(
            'SELECT
                        llx_projet.ref,
                        llx_socpeople.civility,
                        llx_socpeople.firstname,
                        llx_socpeople.lastname,
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.fk_soc,
                        llx_socpeople.town,
                        llx_projet_extrafields.lieuaff
                        FROM llx_element_contact
                        INNER JOIN llx_projet
                        ON llx_element_contact.element_id = llx_projet.rowid  
                        INNER JOIN llx_projet_extrafields
                        ON llx_projet.rowid = llx_projet_extrafields.fk_object
                        INNER JOIN llx_socpeople
                        ON llx_element_contact.fk_socpeople = llx_socpeople.rowid
                        WHERE llx_projet.ref LIKE  :affaire                      
                        AND llx_socpeople.lastname like :nom');

        $getClient->bindParam(':affaire', $affaire);
//        $getClient->bindParam(':prenom', $prenom);
        $getClient->bindParam(':nom', $nom);
        $getClient->execute();
        $client = $getClient->fetch();

        $natContact = 'Client(e)';

//        $db = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getProj = $db->prepare('select   
                        llx_socpeople.civility as clientCiv,                 
                        llx_socpeople.lastname as clientPrenom, 
                        llx_socpeople.firstname as clientNom,
                        llx_socpeople.address as clientAdd,
                        llx_socpeople.zip as clientCP,
                        llx_socpeople.town as clientVille,
                        llx_socpeople.fk_soc,
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle = :client');

        $getProj->bindParam(':ref', $affaire);
        $getProj->bindParam(':client', $natContact);
        $getProj->execute();
        $res = $getProj->fetch();
        $id_soc = $client['fk_soc'];

        $getNameSoc = $db->prepare('SELECT nom as nomSoc FROM llx_societe where rowid = :fk_soc');
        $getNameSoc->bindParam(':fk_soc', $id_soc);
        $getNameSoc->execute();
        $nameSoc = $getNameSoc->fetch();

        if ($pj == 'oui') {

            $getPJ1 = $db->prepare('select            
                        llx_socpeople.lastname,  
                        llx_socpeople.firstname,
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.fk_soc,
                        llx_socpeople.town,
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle = "P.J"');

            $getPJ1->bindParam(':ref', $affaire);
            $getPJ1->execute();
            $PJ1 = $getPJ1->fetch();
            $fkSocPJ = $PJ1['fk_soc'];


            $getPJ2 = $db->prepare('SELECT nom as nomPJ FROM llx_societe where rowid = :fk_soc ');
            $getPJ2->bindParam('fk_soc', $fkSocPJ);
            $getPJ2->execute();
            $PJ2 = $getPJ2->fetch();

        }


        $lieuxExp = $client['lieuaff'];
        $lieuxExp2 = explode(" ", $lieuxExp);
        $count = count($lieuxExp2);
        $lieuxExp3 = '';


        for ($i = 4; $i < $count ; $i++) {
            $lieuxExp3 .= ' ' . $lieuxExp2[$i] . ' ';

        }
        if ($pj == 'oui') {

            $tempTiers = $this->renderView('convocations/modelConvocEse.html.twig', [
                'client' => $client,
                'dateRdv' => $date,
                'heureRdv' => $heure,
                'expert' => $expert,
                'dateDesordre' => $dateDesordre,
                'natureTrav' => $natureTrav,
                'lieuxExp' => utf8_encode($lieuxExp3),
                'vref' => $vref,
                'res' => $res,
                'nameSoc' => $nameSoc,
                'numReco' => $numReco,
                'pj' => $PJ2,
                'interlocuteur' => $interlocuteur,
                'dossier' => $dossier

            ]);
        }
        else {
            $tempTiers = $this->renderView('convocations/modelConvocEse.html.twig', [
                'client' => $client,
                'dateRdv' => $date,
                'heureRdv' => $heure,
                'expert' => $expert,
                'dateDesordre' => $dateDesordre,
                'natureTrav' => $natureTrav,
                'lieuxExp' => utf8_encode($lieuxExp3),
                'vref' => $vref,
                'res' => $res,
                'pj' => '',
                'nameSoc' => $nameSoc,
                'numReco' => $numReco,
                'interlocuteur' => $interlocuteur,
                'dossier' => $dossier

            ]);
        }

        $html2pdf = new html2pdf('P', 'A4', 'fr', true, 'utf-8', 10);

        $html2pdf->writeHTML($tempTiers);
        $html2pdf->setTestTdInOnePage(true);
        return $html2pdf->output('convocationTiers' . $affaire . '.pdf');
    }

    /**
     * **********************************************************************************************************************************************************************************
     *                                                      Partie Assurance tiers
     * **********************************************************************************************************************************************************************************
     */

    /**
     * Affiche le formulaire de convocs clients
     * @Route("convocationAssEse", name="convocationAssEse")
     */
    public function getFormAssTiers() :Response
    {



        return  $this->render('convocations/formConvocAssTiers1.html.twig');
    }

    /**
     * @return Response
     * @Route("convocationsAssEse2", name="convocationsAssEse2")
     */
    public function getProject2()
    {

        $ref = '%' . $_POST['refAffaire'] . '%';
        $client = 'Client(e)';
        $entreprise = 'Entreprise' . '%';
        $assureur = 'Assureur' . '%';


        $db = new PDOConnection('mysql:host=localhost;dbname=' . self::dbLocal . '', '' . self::usrLocal . '', '' . self::mdpLocal . '');
        $getEse = $db->prepare('select                    
                        llx_socpeople.lastname, 
                        llx_socpeople.firstname, 
                        llx_socpeople.rowid, 
                        llx_projet.ref,
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle like :entreprise
                        ');

        $getEse->bindParam(':ref', $ref);
        $getEse->bindParam(':entreprise', $entreprise);
//        $getProj->bindParam(':client', $client);
        $getEse->execute();

        $Ese = $getEse->fetchAll();

        $db = new PDOConnection('mysql:host=localhost;dbname=' . self::dbLocal . '', '' . self::usrLocal . '', '' . self::mdpLocal . '');
        $getAss = $db->prepare('select                    
                        llx_socpeople.lastname, 
                        llx_socpeople.firstname, 
                        llx_socpeople.rowid, 
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle like :assureur
                        ');

        $getAss->bindParam(':ref', $ref);
        $getAss->bindParam(':assureur', $assureur);
//        $getProj->bindParam(':client', $client);
        $getAss->execute();

        $Ass = $getAss->fetchAll();

        return $this->render('convocations/formConvocAssTiers.html.twig', [

            'ese' => $Ese,
            'ass' => $Ass,
            'affaire' => $ref

        ]);
    }
    /**
     * Récupèration des infos passées au formulaire et Génération du PDF
     * @Route("generateConvAss", name="generateConvAss")
     */
    public function getConvocAssurance() {

        $ref2 =  '%' .$_POST['affaire'] . '%';
        $date = $_POST['datepicker'];
        $heure = $_POST['timepicker'];
        $expert = $_POST['expert'];
        $texteLibre = $_POST['textelibre'];
        $client = 'Client(e)';
        $assureur = 'Assureur';
        $entreprise = 'Entreprise';
        $numReco = $_POST['numReco'];
        $vref = $_POST['vref'];
        $dateDes = $_POST['dateDesordre'];
        $pj = $_POST['pj'];
        $interlocuteur = $_POST['interlocuteur'];
        $numDoss = $_POST['dossier'];

        /**
         * requête qui récupère les coordonées d'un client lié à une affaire particulière
         */
        $db2 = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getSoc = $db2->prepare ('select 
                        llx_projet.ref,
                        llx_socpeople.civility,
                        llx_socpeople.lastname, 
                        llx_socpeople.firstname, 
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.town,
                        llx_projet_extrafields.lieuaff,
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        INNER JOIN llx_projet_extrafields ON llx_projet.rowid = llx_projet_extrafields.fk_object
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160 
                        and llx_c_type_contact.libelle like :client');
        $getSoc->bindParam(':ref', $ref2);
        $getSoc->bindParam(':client', $client);

        $getSoc->execute();
        $client = $getSoc->fetch();
        $refAff = $client['ref'];


        $db = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getAss = $db->prepare ('select 
                        llx_projet.ref,
                        llx_socpeople.fk_soc,
                        llx_socpeople.civility,
                        llx_socpeople.lastname, 
                        llx_socpeople.firstname, 
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.town,
                        llx_projet_extrafields.lieuaff,
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        INNER JOIN llx_projet_extrafields ON llx_projet.rowid = llx_projet_extrafields.fk_object
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160 
                        and llx_c_type_contact.libelle like :assureur');
        $getAss->bindParam(':ref', $ref2);
        $getAss->bindParam(':assureur', $assureur);

        $getAss->execute();
        $assurance = $getAss->fetch();

        $fk_soc = $assurance['fk_soc'];


        $db3 = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getSocAss = $db3->prepare('SELECT nom from llx_societe where rowid = :fk_soc');

        $getSocAss->bindParam(':fk_soc', $fk_soc);

        $getSocAss->execute();
        $socAss = $getSocAss->fetch();

        $db4 = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getSoc2 = $db4->prepare('select 
                        llx_projet.ref,
                        llx_socpeople.fk_soc as fk_soc2,
                        llx_socpeople.civility,
                        llx_socpeople.lastname, 
                        llx_socpeople.firstname, 
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.town,
                        llx_projet_extrafields.lieuaff,
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        INNER JOIN llx_projet_extrafields ON llx_projet.rowid = llx_projet_extrafields.fk_object
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160 
                        and llx_c_type_contact.libelle like :entreprise ');
        $getSoc2->bindParam(':entreprise', $entreprise);
        $getSoc2->bindParam(':ref', $ref2);
        $getSoc2->execute();
        $Ese = $getSoc2->fetch();

        $fk_socEse = $Ese['fk_soc2'];

        $db3 = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $ref3 = $Ese['ref'];
        $getEse = $db3->prepare('SELECT nom from llx_societe where rowid = :fk_soc');
        $getEse->bindParam(':fk_soc', $fk_socEse);

        $getEse->execute();
        $nomEse = $getEse->fetch();

        //L'adresse d'expertise contenant des caractères en trop, je l'explose et exploite seulement les fragments dont j'ai besoin
        $lieuxExp = $client['lieuaff'];
        $lieuxExp2 = explode(" ", $lieuxExp);
        $count = count($lieuxExp2);
        $lieuxExp3 = '';
        for($i = 4; $i < $count  ; $i++) {
            $lieuxExp3 .=  ' ' . $lieuxExp2[$i] . ' ';

        }


        if($pj == 'oui')
        {
            $getPJ1 = $db->prepare('select            
                        llx_socpeople.lastname,  
                        llx_socpeople.firstname,
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.fk_soc,
                        llx_socpeople.town,
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle = "P.J"');

            $getPJ1->bindParam(':ref', $ref2);
            $getPJ1->execute();
            $PJ1 = $getPJ1->fetch();
            $fkSocPJ = $PJ1['fk_soc'];

            $getPJ2 = $db->prepare('SELECT nom as nomPJ FROM llx_societe where rowid = :fk_soc ');
            $getPJ2->bindParam('fk_soc', $fkSocPJ);
            $getPJ2->execute();
            $PJ2 = $getPJ2->fetch();
        }


        if ($pj == 'oui')
        {
            $template = $this->renderView('convocations/modelConvocAssEse.html.twig', [


                'client' => $client,
                'assurance' => $assurance,
                'dateRdv' => $date,
                'heureRdv' => $heure,
                'expert' => $expert,
                'textelibre' => $texteLibre,
                'lieuxexp' => utf8_encode($lieuxExp3),
                'socAss' => $socAss,
                'nomEse' => $nomEse,
                'vref' => $vref,
                'numReco' => $numReco,
                'pj' => $PJ2,
                'dateDes' => $dateDes,
                'interlocuteur' => $interlocuteur,
                'dossier' => $numDoss

            ]);


        } else

            $template = $this->renderView('convocations/modelConvocAssEse.html.twig', [


                'client' => $client,
                'assurance' => $assurance,
                'dateRdv' => $date,
                'heureRdv' => $heure,
                'expert' => $expert,
                'textelibre' => $texteLibre,
                'lieuxexp' => utf8_encode($lieuxExp3),
                'socAss' => $socAss,
                'nomEse' => $nomEse,
                'vref' => $vref,
                'numReco' => $numReco,
                'pj' => '',
                'dateDes' => $dateDes,
                'interlocuteur' => $interlocuteur,
                'dossier' => $numDoss

            ]);

        {

        }
        $html2pdf = new html2pdf('P', 'A4', 'fr', true, 'utf-8', 10);
        $html2pdf->setTestTdInOnePage(false);
        $html2pdf->writeHTML($template);

        return $html2pdf->output('convocationAssEse'. $refAff .'.pdf');

    }



    /*****************************************************************************************************************************************************************
     *
     *   											PARTIE MULTIRISQUE CLIENT
     * ****************************************************************************************************************************************************************
     */



    /**
     * Affiche le formulaire de convocs clients
     * @Route("convocationAssClt", name="convocationAssClt")
     */
    public function getFormAssClient() :Response
    {

        return  $this->render('convocations/formConvocAssClient.html.twig');
    }

    /**
     * Récupèration des infos passées au formulaire et Génération du PDF
     * @Route("generateConvClt", name="generateConvClt")
     */
    public function getConvocMulti() {

        $ref =  '%' .$_POST['affaire'] . '%';
        $date = $_POST['datepicker'];
        $heure = $_POST['timepicker'];
        $expert = $_POST['expert'];
        $texteLibre = $_POST['textelibre'];
        $client = 'Client(e)';
        $assureur = 'Assurance client';
        $vref = $_POST['vref'];
        $entreprise = 'Entreprise';
        $dateDesordre = $_POST['dateDesordre'];
        $montTrav = $_POST['montTrav'];
        $numReco = $_POST['numReco'];
        $interlocuteur = $_POST['interlocuteur'];

        /**
         * requête qui récupère les coordonées d'un client lié à une affaire particulière
         */
        $db = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getSoc = $db->prepare ('select 
                        llx_projet.ref,
                        llx_socpeople.civility,
                        llx_socpeople.lastname, 
                        llx_socpeople.firstname, 
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.town,
                        llx_projet_extrafields.lieuaff,
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        INNER JOIN llx_projet_extrafields ON llx_projet.rowid = llx_projet_extrafields.fk_object
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160 
                        and llx_c_type_contact.libelle like :client');
        $getSoc->bindParam(':ref', $ref);
        $getSoc->bindParam(':client', $client);

        $getSoc->execute();
        $societe = $getSoc->fetch();


        $refAff = $societe['ref'];


        $db = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getAss = $db->prepare ('select 
                        llx_projet.ref as reffAff,
                        llx_socpeople.fk_soc as idSoc,
                        llx_socpeople.civility,
                        llx_socpeople.lastname, 
                        llx_socpeople.firstname, 
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.town,
                        llx_projet_extrafields.lieuaff,
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        INNER JOIN llx_projet_extrafields ON llx_projet.rowid = llx_projet_extrafields.fk_object
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160 
                        and llx_c_type_contact.libelle like :assureur');
        $getAss->bindParam(':ref', $refAff);
        $getAss->bindParam(':assureur', $assureur);

        $getAss->execute();
        $assurance = $getAss->fetch();

        $fk_soc = $assurance['idSoc'];
        $ref2 = $assurance['reffAff'];



        $db3 = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getSocAss = $db3->prepare('SELECT nom from llx_societe where rowid = :fk_soc');

        $getSocAss->bindParam(':fk_soc', $fk_soc);

        $getSocAss->execute();
        $socAss = $getSocAss->fetch();



        $db4 = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getSoc2 = $db4->prepare('select 
                        llx_projet.ref,
                        llx_socpeople.fk_soc as idEse,
                        llx_socpeople.civility,
                        llx_socpeople.lastname, 
                        llx_socpeople.firstname, 
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.town,
                        llx_projet_extrafields.lieuaff,
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        INNER JOIN llx_projet_extrafields ON llx_projet.rowid = llx_projet_extrafields.fk_object
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref = :ref ) 
                        and fk_c_type_contact != 160 
                        and llx_c_type_contact.libelle like :entreprise ');

        $getSoc2->bindParam(':entreprise', $entreprise);
        $getSoc2->bindParam(':ref', $ref2);
        $getSoc2->execute();
        $Ese = $getSoc2->fetch();

        $fk_socEse = $Ese['idEse'];

        $db3 = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getEse = $db3->prepare('SELECT nom as nomEse from llx_societe where rowid = :fk_soc');

        $getEse->bindParam(':fk_soc', $fk_socEse);

        $getEse->execute();
        $nomEse = $getEse->fetch();

        //L'adresse d'expertise contenant des caractères en trop, je l'explose et exploite seulement les fragments dont j'ai besoin
        $lieuxExp = $societe['lieuaff'];
        $lieuxExp2 = explode(" ", $lieuxExp);
        $count = count($lieuxExp2);
        $lieuxExp3 = '';
        for($i = 4; $i < $count  ; $i++) {
            $lieuxExp3 .=  ' ' . $lieuxExp2[$i] . ' ';

        }


        $template = $this->renderView('convocations/modelConvocAssClt.html.twig', [


            'societe' => $societe,
            'assurance' => $assurance,
            'dateRdv' => $date,
            'heureRdv' => $heure,
            'expert' => $expert,
            'textelibre' => $texteLibre,
            'lieuxexp' => utf8_encode($lieuxExp3),
            'socAss' => $socAss,
            'vref' => $vref,
            'nomEse' => $nomEse,
            'dateDesordre' => $dateDesordre,
            'montTrav' => $montTrav,
            'numReco' => $numReco,
            'interlocuteur' => $interlocuteur

        ]);

        $html2pdf = new html2pdf('P', 'A4', 'fr', true, 'utf-8', 10);
        $html2pdf->setTestTdInOnePage(false);
        $html2pdf->writeHTML($template);

        return $html2pdf->output('convocationAssEse'. $refAff .'.pdf');
    }



}