export const rgbaToValues = rgba => {
    const rgbaValues = rgba.match(num);
    if (rgbaValues?.length < 3) {
        throw new Error(`Invalid rgba ${rgba}`)
    }
    return rgbaValues
}

const num = /[\d.]+/g
