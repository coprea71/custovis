document.addEventListener('alpine:init', () => {
    Alpine.data('ticketCollision', (ticketId, me) => ({
        others: [],
        channel: null,

        join() {
            this.channel = window.Echo.join(`ticket.${ticketId}`)
                .here((users) => {
                    this.others = users.filter((user) => user.id !== me.id);
                })
                .joining((user) => {
                    if (user.id !== me.id) {
                        this.others.push(user);
                    }
                })
                .leaving((user) => {
                    this.others = this.others.filter((u) => u.id !== user.id);
                });

            window.addEventListener('beforeunload', () => this.leave());
        },

        leave() {
            if (this.channel) {
                window.Echo.leave(`ticket.${ticketId}`);
            }
        },

        collisionLabel() {
            if (this.others.length === 0) {
                return '';
            }

            const names = this.others.map((user) => user.name).join(', ');

            return this.others.length === 1
                ? `${names} schaut sich dieses Ticket ebenfalls an`
                : `${names} schauen sich dieses Ticket ebenfalls an`;
        },
    }));
});
