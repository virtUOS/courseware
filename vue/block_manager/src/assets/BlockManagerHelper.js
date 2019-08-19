export default {
    shortTitle(title, length = 5) {
        return title.length > length ? title.substr(0, length - 1) + '…' : title;
    },
    test(resolve) {
        resolve(42);
    }
};
