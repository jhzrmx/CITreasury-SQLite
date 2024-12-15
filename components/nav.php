<?php function nav() { ?>
	<nav class="fixed w-full bg-custom-purple flex flex-row shadow shadow-gray-800">
        <div class="w-1/2 flex flex-row">
            <img src="../img/nobgcitsclogo.png" class="w-12 h-12 my-2 ml-6">
            <h1 class="text-3xl p-3 font-bold text-white">CITreasury</h1>
        </div>
        <div class="w-1/2 flex flex-row justify-end text-white">
            <button id="mdi-menu" class="m-4">
                <svg  class="w-7 h-7 fill-current rounded transition-all duration-300-ease-in-out md:hidden hover:bg-white hover:text-custom-purple hover:cursor-pointer" viewBox="0 0 24 24"><path d="M3,6H21V8H3V6M3,11H21V13H3V11M3,16H21V18H3V16Z" /></svg>
            </button>
        </div>
    </nav>
 <?php } ?>