/**
 * @return {URLSearchParams}
 */
export function objToSearchParams(obj: { [key: string]: string }) {
  if (obj === undefined || obj === null) {
    return new URLSearchParams()
  }
  const params = new URLSearchParams()
  Object.keys(obj).forEach(k => {
    params.append(k.toString(), obj[k] ?? '')
  })
  return params
}
