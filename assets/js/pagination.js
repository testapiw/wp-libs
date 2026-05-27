!(function(){
    window.wplibs = window.wplibs || {};
    window.wplibs.getPagination = () => {
        return {
            props: {
                    totalPages: Number,
                    currentPage: Number,
                    perPage: Number
            },
            emits: ['page-changed', 'per-page'],
            computed: {
                visiblePages() {
                    const pages = [];
                    const total = this.totalPages;
                    const current = this.currentPage;
                    const delta = 2;

                    if (total <= 5) {
                        for (let i = 1; i <= total; i++) pages.push(i);
                    } else {
                        let start = Math.max(2, current - delta);
                        let end = Math.min(total - 1, current + delta);

                        pages.push(1);
                        if (start > 2) pages.push('...');
                        for (let i = start; i <= end; i++) pages.push(i);
                        if (end < total - 1) pages.push('...');
                        pages.push(total);
                    }
                    return pages;
                }
            },
            methods: {
                goToPage(page) {
                    if (page !== '...' && page !== this.currentPage) {
                        this.$emit('page-changed', page);
                    }
                },
                prevPage(event) {
                    event.preventDefault();
                    if (this.currentPage > 1) {
                        this.$emit('page-changed', this.currentPage - 1);
                    }
                },
                nextPage(event) {
                    event.preventDefault();
                    if (this.currentPage < this.totalPages) {
                        this.$emit('page-changed', this.currentPage + 1);
                    }
                },
                onPerpage(event, count) {
                    event.preventDefault();
                    this.$emit('per-page', count || 20);
                }
            },
            template: '#pagination-template'
        }
    }
})();