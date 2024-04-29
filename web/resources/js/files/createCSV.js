export const createCSV = (options) => new CSVBuilder(options)

class CSVBuilder {
    rows = []
    header = []
    separator = ';'
    newline = '\n'

    constructor ({ header, separator, newline } = {}) {
        this.header = header || this.header
        this.separator = separator || this.separator
        this.newline = newline || this.newline
        return this
    }

    addRow (entries) {
        if (entries.length !== this.header.length) {
            throw new Error(`Expected rows with ${this.header.length}, got ${entries.length}`)
        }
        this.rows.push(entries)
        return this
    }

    build () {
        let out = toLine(this.header, this)
        this.rows.forEach(row => {
            out += toLine(row, this)
        })
        return out
    }
}

const toLine = (row, { separator, newline }) => {
    return row.join(separator) + newline
}
