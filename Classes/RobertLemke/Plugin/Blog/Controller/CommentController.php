<?php
namespace RobertLemke\Plugin\Blog\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 package "Blog".                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\TYPO3CR\Domain\Model\NodeTemplate;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Comments controller for the Blog package
 */
class CommentController extends ActionController {

	/**
	 * @Flow\Inject
	 * @var \RobertLemke\Akismet\Service
	 */
	protected $akismetService;

	/**
	 * Initialize the Akismet service
	 *
	 * @return void
	 */
	protected function initializeAction() {
		$this->akismetService->setCurrentRequest($this->request->getHttpRequest());
	}

	/**
	 * Creates a new comment
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $postNode The post node which will contain the new comment
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeTemplate<RobertLemke.Plugin.Blog:Comment> $nodeTemplate
	 * @return void
	 */
	public function createAction(NodeInterface $postNode, NodeTemplate $newComment) {
			# Workaround until we can validate node templates properly:
		if (strlen($newComment->getProperty('author')) < 2) {
			$this->throwStatus(400, 'Your comment was NOT created - please specify your name.');
		}

		if (strlen($newComment->getProperty('text')) < 5) {
			$this->throwStatus(400, 'Your comment was NOT created - it was too short.');
		}

		if (filter_var($newComment->getProperty('emailAddress'), FILTER_VALIDATE_EMAIL) === FALSE) {
			$this->throwStatus(400, 'Your comment was NOT created - you must specify a valid email address.');
		}

		$commentNode = $postNode->getNode('comments')->createNodeFromTemplate($newComment, uniqid('comment-'));
		$commentNode->setProperty('spam', FALSE);
		$commentNode->setProperty('datePublished', new \DateTime());

		if ($this->akismetService->isCommentSpam('', $commentNode->getProperty('text'), 'comment', $commentNode->getProperty('author'), $commentNode->getProperty('emailAddress'))) {
			$commentNode->setProperty('spam', TRUE);
		}

		$this->emitCommentCreated($commentNode, $postNode);
		$this->response->setStatus(201);
		return 'Thank you for your comment! It may take a moment to become visible.';
	}

	/**
	 * Signal which informs about a newly created comment
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $commentNode The comment node
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $postNode The post node
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitCommentCreated(NodeInterface $commentNode, NodeInterface $postNode) {}
}

?>