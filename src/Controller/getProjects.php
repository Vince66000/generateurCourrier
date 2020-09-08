<?php


namespace App\Controller;

use Doctrine\DBAL\Driver\PDOConnection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class getProjects
{

    /**
     * @throws \Doctrine\DBAL\Driver\PDOException
     * @Route("getProj/", name="getProj")
     */
    public function getProjects()
    {

            $ref = $_POST['term'];

            $db = new PDOConnection('mysql:host=localhost;dbname=dolibarr', 'vincent', 'root');

        $req = $db->prepare("SELECT
    P.ref
    FROM llx_projet as P
    WHERE P.ref = :ref ");
        $req->bindParam(":ref",$ref);
        $req->execute();
        $req->fetchAll();



    return new Response($req->body);


    }

}
