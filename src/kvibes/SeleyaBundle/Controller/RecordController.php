<?php

namespace kvibes\SeleyaBundle\Controller;

use kvibes\SeleyaBundle\Entity\Bookmark;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class RecordController extends Controller
{
    /**
     * Shows a record
     *  
     * @param int $id ID of the record
     * @return Rendered template
     */
    public function indexAction($id)
    {
        $securityContext = $this->get('security.context');
        $em = $this->getDoctrine()->getManager();
        $bookmark = null;
        $record = $em->getRepository('SeleyaBundle:Record')
                     ->getRecord($id);

        if ($record === null) {
            throw $this->createNotFoundException('Unable to find record.');
        }

        if ($securityContext->isGranted('ROLE_USER')) {
            $user = $em->getRepository('SeleyaBundle:User')
                       ->getUser($securityContext->getToken()->getUser()->getUsername());
    
            $bookmark = $em->getRepository('SeleyaBundle:Bookmark')
                           ->getBookmarkForRecord($record, $user);
        }

        return $this->render('SeleyaBundle:Record:index.html.twig', array(
            'record'      => $record,
            'hasBookmark' => $bookmark !== null
        ));
    }
    
    /**
     * @Secure(roles="ROLE_USER")
     */ 
    public function toggleBookmarkAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $record = $em->getRepository('SeleyaBundle:Record')
                     ->getRecord($id);
        
        $securityContext = $this->get('security.context');
        $user = $em->getRepository('SeleyaBundle:User')
                   ->getUser($securityContext->getToken()->getUser()->getUsername());
        
        if ($record === null) {
            return new Response('', 400, array('Content-Type'=>'application/json'));
        }

        $hasBookmark = false;
        $bookmark = $em->getRepository('SeleyaBundle:Bookmark')
                       ->getBookmarkForRecord($record, $user);
        if ($bookmark === null) {
            $bookmark = new Bookmark();
            $bookmark->setRecord($record);
            $bookmark->setUser($user);
            $em->persist($bookmark);
            $em->flush();
            $hasBookmark = true;
        } else {
            $em->remove($bookmark);
            $em->flush();
        }
        
        return new Response(
            json_encode(
                array(
                    'success'  => true,
                    'bookmark' => $hasBookmark
                )
            ), 
            200, 
            array(
                'Content-Type' => 'application/json'
            )
        );
    }
}
