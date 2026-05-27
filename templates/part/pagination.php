<?php 
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
?>

<template id="pagination-template">
    <div class="btn-group">
        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            Per pages, {{perPage > 100 ? 'all': perPage}}
        </button>
        <ul class="dropdown-menu">
            <li @click="onPerpage($event, 20)"><a class="dropdown-item" href="#">20</a></li>
            <li @click="onPerpage($event, 50)"><a class="dropdown-item" href="#">50</a></li>
            <li @click="onPerpage($event, 100)"><a class="dropdown-item" href="#">100</a></li>
            <li @click="onPerpage($event, 1000)"><a class="dropdown-item" href="#">All</a></li>
        </ul>
    </div>
    <ul class="pagination-wrapper pagination-sm justify-content-end" v-show="totalPages > 1">

            <li class="page-item" @click.prevent="prevPage($event, $event)" :disabled="currentPage === 1"><a class="page-link" href="#">Prev</a></li>

            <li 
                v-for="page in visiblePages" 
                :key="page"
                class="page-item"
                :class="{ 'btn-primary': page === currentPage }"
                :disabled="page === '...'"
                @click.prevent="goToPage(page)">
                <a class="page-link" href="#">{{ page }}</a>
            </li>

            <li class="page-item" @click.prevent="nextPage($event)" :disabled="currentPage === totalPages"><a class="page-link" href="#">Next</a></li>
    
    </ul>
</template>