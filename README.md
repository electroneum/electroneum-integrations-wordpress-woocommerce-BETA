# ElectroneumWP
A WooCommerce extension for accepting Electroneum (ETN)

IMPORTANT: THIS IS CURRENTLY IN BETA - DO NOT USE IN A PRODUCTION ENVIRONMENT.

We are porting this serhack's code to work with Electroneum and we'd made several improvements. It will be ready for release when we move from a cookie based system to a database system, which is in development.

This code is to demonstrate how easy it is for vendors to confirm ETN payments without running a full node, using the Electroneum block explorer (http://blockexplorer.electroneum.com)

## Dependancies
This plugin is rather simple but there are a few things that need to be set up before hand.

* A web server! Ideally with the most recent versions of PHP and mysql

* An Electroneum wallet. Offline Wallet or CLI Wallet would be better since they have private view key for transaction validation. You can find the official wallet [here](https://electroneum.com/), Offline Wallet [here](https://downloads.electroneum.com) and CLI Wallet [here](https://github.com/electroneum/electroneum-pool#1-downloading--installing)

* [WordPress](https://wordpress.org)
Wordpress is the backend tool that is needed to use WooCommerce and this Electroneum plugin

* [WooCommerce](https://woocommerce.com)
This Electroneum plugin is an extension of WooCommerce, which works with WordPress

## Step 1: Activating the plugin
* Downloading: First of all, you will need to download the plugin. You can download the latest release as a .zip file from https://github.com/rajnirvanalabs/electroneumwp If you wish, you can also download the latest source code from GitHub. This can be done with the command `git clone https://github.com/rajnirvanalabs/electroneumwp.git` or can be downloaded as a zip file from the GitHub web page.

* Unzip the file electroneumwp-master.zip if you downloaded the zip from the master page [here](https://github.com/rajnirvanalabs/electroneumwp).

* Put the plugin in the correct directory: You will need to put the folder named `electroneum` from this repo/unzipped release into the wordpress plugins directory. This can be found at `path/to/wordpress/folder/wp-content/plugins`

* Activate the plugin from the WordPress admin panel: Once you login to the admin panel in WordPress, click on "Installed Plugins" under "Plugins". Then simply click "Activate" where it says "Electroneum - WooCommerce Gateway"

## Step 2 Option 1: Use your wallet address and viewkey

* Get your Electroneum wallet address starting with 'etnk'
* Get your wallet secret viewkey from your wallet (Available only for Paper Wallet and CLI Wallet)

A note on privacy: When you validate transactions with your private viewkey, your viewkey is sent to (but not stored on) blockexplorer.electroneum.com over HTTPS. This could potentially allow an attacker to see your incoming, but not outgoing, transactions if he were to get his hands on your viewkey. Even if this were to happen, your funds would still be safe and it would be impossible for somebody to steal your money. For maximum privacy use your own electroneum-wallet-rpc instance.

## Step 2 Option 2: Get a electroneum daemon to connect to

### Option 1: Running a full node yourself

To do this: start the electroneum daemon on your server and leave it running in the background. This can be accomplished by running `./electroneumd` inside your electroneum downloads folder. The first time that you start your node, the electroneum daemon will download and sync the entire electroneum blockchain. This can take several hours and is best done on a machine with at least 4GB of ram, an SSD hard drive (with at least 40GB of free space), and a high speed internet connection.
You can refer the official documentation for running full node from [here](https://github.com/electroneum/electroneum) and for wallet rpc from [here](https://github.com/electroneum/electroneum-pool#1-downloading--installing).

### Option 2: Connecting to a remote node
The easiest way to find a remote node to connect to is to visit [ElectroneumPool](https://github.com/electroneum/electroneum-pool#pools-using-this-software) and use one of the nodes offered which supports json_rpc. `Eg. https://etn.mymininghub.com/json_rpc port 445`

### Setup your  electroneum wallet-rpc

* Setup a electroneum wallet using the electroneum-wallet-cli tool. If you do not know how to do this you can learn about it at [https://github.com/electroneum/electroneum-pool](https://github.com/electroneum/electroneum-pool#1-downloading--installing)
You can checkout the electroneum wallet commands from [here](https://github.com/electroneum/electroneum/wiki/Wallet-Commands-(CLI))


* Start the Wallet RPC and leave it running in the background. This can be accomplished by running `electroneum-wallet-rpc --wallet-file /path/to/wallet/file --password walletPassword --rpc-bind-port 26969 --disable-rpc-login` where "/path/to/wallet/file" is the wallet file for your electroneum wallet. If you wish to use a remote node you can add the `--daemon-address` flag followed by the address of the node. `--daemon-address etn.mymininghub.com:443` for example.

`Note: You must run your JSON RPC on the host server of WooCommerce against your wallet`

## Step 4: Setup Electroneum Gateway in WooCommerce

* Navigate to the "settings" panel in the WooCommerce widget in the WordPress admin panel.

* Click on "Checkout"

* Select "Electroneum GateWay"

* Check the box labeled "Enable this payment gateway"

* Check either "Use ViewKey" or "Use electroneum-wallet-rpc"

If You chose to use viewkey:

* Enter your electroneum wallet address in the box labled "Electroneum Address". If you do not know your address, you can run the `address` commmand in your electroneum wallet

* Enter your secret viewkey in the box labeled "ViewKey"

If you chose to use electroneum-wallet-rpc:

* Enter your electroneum wallet address in the box labled "Electroneum Address". If you do not know your address, you can run the `address` commmand in your electroneum wallet

* Enter the IP address of your server in the box labeled "Electroneum wallet rpc Host/IP"

* Enter the port number of the Wallet RPC in the box labeled "Electroneum wallet rpc port" (will be `26969` if you used the above example).

Finally:

* Click on "Save changes"
