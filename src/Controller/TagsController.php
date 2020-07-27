<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\NoteRepository;
use App\Repository\TagRepository;
use App\Entity\Note;
use App\Entity\User;
use App\Entity\Tag;

class TagsController extends ApiController
{
    protected function isTag($tagId) 
    {
        if (!(is_int($tagId))) {     
            return ['success' => false, 'error' => 'Invalid tag id'];
        }  

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $tag = $this->getDoctrine()->getRepository(Tag::class)->findById($user, $tagId);

        if ($tag === null) {
            return ['success' => false, 'error' => 'User does not have this tag: ' . $tagId];
        }
        return ['success' => true, 'tag' => $tag];
    }
    /**
     * @Route("/api/set-tag", methods={"POST"})
     */
    public function setTag(Request $request) 
    {
        $request = $this->transformJsonBody($request);
        $body = $request->get('body');

        if (empty($body)) {
            return $this->respondError('Invalid body');
        }

        $user = $this->get('security.token_storage')->getToken()->getUser();

        if ($this->getDoctrine()->getRepository(Tag::class)->findByBody($user, $body) !== null) {
            return $this->respondError('This tag already exists');
        }

        $entityManager = $this->getDoctrine()->getManager();
        
        $tag = new Tag();
        $tag->setBody($body)
            ->setUser($user)
        ;

        $entityManager->persist($tag);
        $entityManager->flush();
    
        return $this->respondWithSuccess(sprintf('Tag %s successfully created', $tag->getId()));
    }

    /**
     * @Route("/api/get-tags", methods={"POST"})
     */
    public function getTags() 
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $tagsObjects = $this->getDoctrine()->getRepository(Tag::class)->findByUser($user);
        $tags = [];
        foreach ($tagsObjects as $key => $tag) {
            $tags[$key]['id'] = $tag->getId();
            $tags[$key]['body'] = $tag->getBody();
        }
        return new JsonResponse(['tags' => $tags]);
    }

    /**
     * @Route("/api/get-tag", methods={"POST"})
     */
    public function getTag(Request $request) 
    {
        $request = $this->transformJsonBody($request);
        $result = $this->isTag($request->get('tag'));

        if (!$result['success']) {     
            return $this->respondError($result['error']);
        }  

        $tag = ['id' => $result['tag']->getId(),
                'body' => $result['tag']->getBody(),
        ];

        return new JsonResponse(['tag' => $tag]);
    }

    /**
     * @Route("/api/del-tag", methods={"POST"})
     */
    public function delTag(Request $request) 
    {
        $request = $this->transformJsonBody($request);
        $result = $this->isTag($request->get('tag'));

        if (!$result['success']) {     
            return $this->respondError($result['error']);
        } 

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($result['tag']);
        $entityManager->flush();

        return $this->respondWithSuccess(sprintf('Tag successfully deleted'));
    }

    /**
     * @Route("/api/del-tags", methods={"POST"})
     */
    public function delTags() 
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $del = $this->getDoctrine()->getRepository(Tag::class)->delByUser($user);
        
        return $this->respondWithSuccess(sprintf('%s Tags successfully deleted', $del));
    }

    /**
     * @Route("/api/change-tag-body", methods={"POST"})
     */
    public function changeTagBody(Request $request) 
    {
        $request = $this->transformJsonBody($request);
        $result = $this->isTag($request->get('tag'));

        if (!$result['success']) {     
            return $this->respondError($result['error']);
        }  
        $body = $request->get('body');
        if (empty($body)) {
            return $this->respondError('body is empty');
        }
        $entityManager = $this->getDoctrine()->getManager();
        $result['tag']->setBody($body);
        $entityManager->flush();
        
        return $this->respondWithSuccess(sprintf('Tag successfully updated'));
    }
}
