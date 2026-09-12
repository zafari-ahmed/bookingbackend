export function auditTable(rows = []) {
    return {
        rows,
        query: '',
        page: 1,
        perPage: 10,

        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (! q) {
                return this.rows;
            }

            return this.rows.filter((row) => (
                `${row.time} ${row.user} ${row.action} ${row.description}`.toLowerCase().includes(q)
            ));
        },

        get pages() {
            return Math.max(1, Math.ceil(this.filtered.length / this.perPage));
        },

        get pageRows() {
            const start = (this.page - 1) * this.perPage;
            return this.filtered.slice(start, start + this.perPage);
        },

        get summary() {
            if (! this.filtered.length) {
                return 'Showing 0 records';
            }

            const start = ((this.page - 1) * this.perPage) + 1;
            const end = Math.min(this.page * this.perPage, this.filtered.length);

            return `Showing ${start} to ${end} of ${this.filtered.length} records`;
        },
    };
}
