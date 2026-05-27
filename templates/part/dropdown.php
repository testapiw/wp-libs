<?php 
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
?>

<template id="dropdown-template">
    <div class="dropdown-wrapper" v-show="list && list.length > 1">
                  
        <div class="btn-group" :class="[usebadge && 'btn-badge']">
            <button type="button" 
                class="btn btn-primary btn-sm dropdown-toggle"
                data-bs-toggle="dropdown"
               >
               <span :class="getButtonClass()"> {{ selectedLabel || placeholder }} </span>
            </button>
            <ul class="dropdown-menu">
                <li v-for="item in list" :key="getItemKey(item)">
                    <span class="dropdown-item" @click.prevent="selectItem(item)">
                        <span :class="getColor(item)">{{ getItemLabel(item) }}</span>
                    </span>
                </li>    
            </ul>
        </div>

    </div>
</template>