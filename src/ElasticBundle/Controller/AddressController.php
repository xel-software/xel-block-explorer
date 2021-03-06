<?php

namespace ElasticBundle\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AddressController
 * @package ElasticBundle\Controller
 */
class AddressController extends AbstractBaseController
{
    public function showAction(Request $request, $address)
    {

        $address = strtoupper($address);

        $elasticManager = $this->get('elastic.manager.elastic');
        $accountInfo = $elasticManager->getAccount($address, true);
        $accountTransactions = $elasticManager->getBlockchainTransactions($address, 0, 99);
        $accountBlocks = $elasticManager->getAccountBlocks($address, false, 0, 99);
        $accountLedger = $elasticManager->getAccountLedger($address, 0, 99);
        $accountWork = $elasticManager->getAccountWork($address, 0, 99);

        if(!$accountInfo) {

            throw $this->createNotFoundException();

        }

        return $this->render('ElasticBundle:Account:index.html.twig',[
            'accountInfo' => $accountInfo,
            'accountBlocks' => $accountBlocks,
            'accountTransactions' => $accountTransactions,
            'accountLedger' => $accountLedger,
            'accountWork' => $accountWork
        ]);
    }
}
