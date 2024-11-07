export const createDelta = selections => {
    const points = []

    let depth = 0

    selections.forEach(selection => {
        points.push([selection.start, selection])
        points.push([selection.end, selection])
    })
    points.sort((a, b) => a[0] - b[0])
    points.forEach((p, i) => console.debug(p[0], p[1].id))
}

const createInterSection = ({ start, end, selections: [] }) => {

}

class Intersection {
    constructor ({ start, end }) {
        this.start = start
        this.end = end
        this.selections = []
    }
}
