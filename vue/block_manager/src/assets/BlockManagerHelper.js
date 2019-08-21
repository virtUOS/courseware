export default {
    shortTitle(title, length = 5) {
        return title.length > length ? title.substr(0, length - 1) + '…' : title;
    },
    getReadableDate(date) {
        let datetime = new Date(date);
        return (
            ('0' + datetime.getDate()).slice(-2) +
            '.' +
            ('0' + (datetime.getMonth() + 1)).slice(-2) +
            '.' +
            datetime.getFullYear()
        );
    }
};
