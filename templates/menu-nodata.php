<?php 
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
?>

<div class="wrap" id="nodata-currency-manager" v-cloak>
    <div id="message" v-if="message" :class="messageClass">{{ message }}</div>
    <div class="wrap-currencys-list">
   
        <div class="filters mb-4">
        <div class="d-flex flex-wrap align-items-end gap-3 filter-params">

            <input type="text"
                placeholder="Search by code"
                v-model="filters.code"
                @input="debouncedSearch"
                class="form-control form-control-sm"
                style="width: 200px; min-width: 150px;" />

            <div>Total: <span v-show="total > 0">{{ total }}</span></div>

            <div v-if="currencies.length" class="ms-auto d-flex">
                <pagination
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    :per-page="perPage"
                    @page-changed="onPageChanged"
                    @per-page="onPerpage"
                />
            </div>

        </div>
        </div>

        <div v-if="isLoading" class="spinner-overlay">
            <div class="spinner"></div>
        </div>
        <div class="currency-table">
            <div class="currency-row header">
                <div>#</div>
                <div>ID</div><div></div><div class="column-left">Code</div>
                <div class="sort-column" @click="sortBy('price')">
                    Price, $
                    <span class="sort-icon dashicons" 
                        :class="{
                            'dashicons-arrow-up-alt2': sort.column === 'price' && sort.order === 'ASC',
                            'dashicons-arrow-down-alt2': sort.column === 'price' && sort.order === 'DESC',
                            'dashicons-sort': sort.column !== 'price' || sort.order === null
                        }">
                    </span>

                </div>
                <div class="sort-column column-rignt" @click="sortBy('state_at')">
                    Date Spotted 
                    <span class="sort-icon dashicons"
                        :class="{
                            'dashicons-arrow-up-alt2': sort.column === 'state_at' && sort.order === 'ASC',
                            'dashicons-arrow-down-alt2': sort.column === 'state_at' && sort.order === 'DESC',
                            'dashicons-sort': sort.column !== 'state_at' || sort.order === null
                        }">
                    </span>
                </div>
                <div class="sort-column column-rignt" @click="sortBy('days_state_at')">
                    Days with no data 
                    <span class="sort-icon dashicons"
                        :class="{
                            'dashicons-arrow-up-alt2': sort.column === 'days_state_at' && sort.order === 'ASC',
                            'dashicons-arrow-down-alt2': sort.column === 'days_state_at' && sort.order === 'DESC',
                            'dashicons-sort': sort.column !== 'days_state_at' || sort.order === null
                        }">
                    </span>
                </div>
            </div>
            <div v-for="(currency, index) in currencies" :key="currency.id" class="currency-row">
                <div>{{ (currentPage - 1) * perPage + index + 1 }}</div>
                <div>{{ currency.id }}</div>
                <div class="currency-icon"><img :src="currency.image" width="36" height="36" /></div>
                <div class="name"><span class="currency-code">{{ currency.currency_code }}</span><span> {{ currency.name }}</span></div>
                <div>{{ currency.price }}</div>
                <div class="column-rignt">{{ currency.format_state_at }}</div>
                <div style="justify-content: center;">{{ currency.days_state_at }}</div>
            </div>
        </div>

        <div class="filters d-flex justify-content-end">
            <pagination
                :current-page="currentPage"
                :total-pages="totalPages"
                :per-page="perPage"
                @page-changed="onPageChanged"
                @per-page="onPerpage"
            />
        </div>

    </div>
</div>
<div id="notification-container"></div>

<?php
echo App\Templates::render('part/pagination');
?>