<?php

namespace Elgentos\Magento\Command\System\Store\Config;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BaseUrlSetCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
      $this
          ->setName('sys:store:config:base-url:set')
          ->setDescription('Set base-urls for installed storeviews [elgentos]')
      ;
    }

   /**
    * @param \Symfony\Component\Console\Input\InputInterface $input
    * @param \Symfony\Component\Console\Output\OutputInterface $output
    * @return int|void
    */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {
           $config = $this->_getModel('core/config','Mage_Core_Model_Config');
           $dialog = $this->getHelperSet()->get('dialog');
            
           $store = $this->getHelper('parameter')->askStore($input, $output);
           $baseURL = $dialog->ask($output, '<question>Base URL: </question>');
           
           $parsed = parse_url($baseURL);
           $path = null;
           
           /* Check if given string can be parsed to a host name */
           if(isset($parsed['host'])) {
               $hostname = $parsed['host'];
               /* Take path into consideration for Magento installs in subdirs */
               if(isset($parsed['path'])) $path = $parsed['path'];
           } else {
               /* If hostname is not recognized, assume path for hostname */
               $parts = explode('/', $parsed['path']);
               if(count($parts)==1) {
                   $hostname = $parts[0];
               } elseif(count($parts)==2) {
                   $hostname = $parts[0];
                   $path = $parts[1];
               }
           }
           $path = trim($path,'/');
           
           /* Set & ask for confirmation of default HTTP and HTTPS hostnames */
           $defaultUnsecure = 'http://' . $hostname . '/';
           if($path) $defaultUnsecure .= $path . '/';
           $unsecureBaseURL = $dialog->ask($output, '<question>Unsecure base URL?</question> <comment>[' . $defaultUnsecure . ']</comment>', $defaultUnsecure);
           $defaultSecure = str_replace('http','https',$defaultUnsecure);
           $secureBaseURL = $dialog->ask($output, '<question>Secure base URL?</question> <comment>[' . $defaultSecure . ']</comment>', $defaultSecure);
           
           $config->saveConfig(
                'web/unsecure/base_url',
                $unsecureBaseURL,
                ($store->getStoreId() === 0 ? 'default' : 'stores'),
                $store->getStoreId()
           );
           $output->writeln('<info>Unsecure base URL for store ' . $store->getName() . ' [' . $store->getCode() . '] set to ' .  $unsecureBaseURL . '</info>');
           
           $config->saveConfig(
                'web/secure/base_url',
                $secureBaseURL,
                ($store->getStoreId() === 0 ? 'default' : 'stores'),
                $store->getStoreId()
           );
           $output->writeln('<info>Secure base URL for store ' . $store->getName() . ' [' . $store->getCode() . '] set to ' .  $secureBaseURL . '</info>');
        }
    }
}